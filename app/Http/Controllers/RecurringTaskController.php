<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Task;
use App\Models\User;
use App\Helper\Files;
use App\Helper\Reply;
use App\Models\Project;
use App\Models\SubTask;
use App\Models\TaskFile;
use App\Models\TaskUser;
use App\Models\TaskLabel;
use App\Models\SubTaskFile;
use App\Models\TaskSetting;
use App\Models\TaskCategory;
use Illuminate\Http\Request;
use App\Models\TaskLabelList;
use App\Models\ProjectTimeLog;
use App\Models\TaskboardColumn;
use App\Traits\ProjectProgress;
use App\Models\ProjectMilestone;
use App\DataTables\RecurringTasksDataTable;
use App\DataTables\TasksDataTable;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Tasks\StoreTask;
use App\Http\Requests\Tasks\UpdateTask;
use App\Events\TaskEvent;
use App\Helper\UserService;
use App\Models\ClientContact;

class RecurringTaskController extends AccountBaseController
{

    use ProjectProgress;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.taskRecurring';
        $this->middleware(
            function ($request, $next) {
                abort_403(!in_array('tasks', $this->user->modules));

                return $next($request);
            }
        );
    }

    public function index(RecurringTasksDataTable $dataTable)
    {
        $viewPermission = user()->permission('view_tasks');

        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        if (!request()->ajax()) {
            $this->assignedTo = request()->assignedTo;

            if (request()->has('assignee') && request()->assignee == 'me') {
                $this->assignedTo = user()->id;
            }

            $this->projects = Project::allProjects();

            if (in_array('client', user_roles())) {
                $this->clients = User::client();
            }
            else {
                $this->clients = User::allClients();
            }

            $this->employees = User::allEmployees(null, true, ($viewPermission == 'all' ? 'all' : null));
            $this->taskBoardStatus = TaskboardColumn::all();
            $this->taskCategories = TaskCategory::all();
            $this->taskLabels = TaskLabelList::all();
            $this->milestones = ProjectMilestone::all();

            $taskBoardColumn = TaskboardColumn::waitingForApprovalColumn();

            $projectIds = Project::where('project_admin', user()->id)->pluck('id');

            if (!in_array('admin', user_roles()) && (in_array('employee', user_roles()) && $projectIds->isEmpty())) {
                $user = User::findOrFail(user()->id);
                $this->waitingApprovalCount = $user->tasks()->where('board_column_id', $taskBoardColumn->id)->where('company_id', company()->id)->count();
            }elseif(!in_array('admin', user_roles()) && (in_array('employee', user_roles()) && !$projectIds->isEmpty())) {
                $this->waitingApprovalCount = Task::whereIn('project_id', $projectIds)->where('board_column_id', $taskBoardColumn->id)->where('company_id', company()->id)->count();
            }else{
                $this->waitingApprovalCount = Task::where('board_column_id', $taskBoardColumn->id)->where('company_id', company()->id)->count();
            }
        }

        return $dataTable->render('recurring-task.index', $this->data);
    }

    /**
     * XXXXXXXXXXX
     *
     * @return array
     */
    public function applyQuickAction(Request $request)
    {
        switch ($request->action_type) {
        case 'delete':
            $result = $this->deleteRecords($request);

            if ($result == true) {

                return response()->json([
                    'status' => 'success',
                    'message' => __('messages.deleteSuccess'),
                    'redirectUrl' => route('recurring-task.index')
                ]);
            }

            return Reply::success(__('messages.deleteSuccess'));
        case 'change-status':
            $this->changeBulkStatus($request);

            return Reply::success(__('messages.updateSuccess'));
        case 'milestone':
            $this->changeMilestones($request);

            return Reply::success(__('messages.updateSuccess'));
        default:
            return Reply::error(__('messages.selectAction'));
        }
    }

    protected function deleteRecords($request)
    {
        abort_403(user()->permission('delete_tasks') != 'all');
        $ids = explode(',', $request->row_ids);
        $tasks = Task::whereIn('id', $ids)->get();

        // Check if any recurring_task_id of these tasks also exists in the selected IDs
        $hasRecurringParentIncluded = $tasks->contains(function ($task) use ($ids) {
            return $task->recurring_task_id && in_array($task->recurring_task_id, $ids);
        });
        Task::whereIn('id', $ids)->delete();

        return $hasRecurringParentIncluded;
    }

    protected function changeBulkStatus($request)
    {
        abort_403(user()->permission('edit_tasks') != 'all');

        $taskBoardColumn = TaskboardColumn::findOrFail(request()->status);

        // Update tasks based on the requested status
        $taskIds = explode(',', $request->row_ids);

        if ($taskBoardColumn && $taskBoardColumn->slug == 'completed') {
            Task::whereIn('id', $taskIds)->update([
                'status' => 'completed',
                'board_column_id' => $request->status,
                'completed_on' => now()->format('Y-m-d')
            ]);
        }
        else {
            Task::whereIn('id', $taskIds)->update(['board_column_id' => $request->status]);
        }

    }

    public function changeMilestones($request)
    {
        abort_403(user()->permission('edit_tasks') != 'all');

        $taskIds = explode(',', $request->row_ids);

        Task::whereIn('id', $taskIds)->update([
            'milestone_id' => $request->milestone
        ]);
    }

    public function destroy(Request $request, $id, RecurringTasksDataTable $dataTable)
    {
        $task = Task::with('project')->findOrFail($id);

        $this->deletePermission = user()->permission('delete_tasks');

        $taskUsers = $task->users->pluck('id')->toArray();
        $redirectUrl = false;

        abort_403(
            !($this->deletePermission == 'all'
                || ($this->deletePermission == 'owned' && in_array(user()->id, $taskUsers))
                || ($task->project && ($task->project->project_admin == user()->id))
                || ($this->deletePermission == 'added' && $task->added_by == user()->id)
                || ($this->deletePermission == 'both' && (in_array(user()->id, $taskUsers) || $task->added_by == user()->id))
                || ($this->deletePermission == 'owned' && (in_array('client', user_roles()) && $task->project && ($task->project->client_id == user()->id)))
                || ($this->deletePermission == 'both' && (in_array('client', user_roles()) && ($task->project && ($task->project->client_id == user()->id)) || $task->added_by == user()->id))
            )
        );

        Task::where('recurring_task_id', $id)->delete();

        // Delete current task
        $task->delete();

        $remainingTask = Task::where('id', $id)->orWhere('recurring_task_id', $id)->count();

        if($remainingTask == 0){

            $viewPermission = user()->permission('view_tasks');

            abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

            if (!request()->ajax()) {
                $this->assignedTo = request()->assignedTo;

                if (request()->has('assignee') && request()->assignee == 'me') {
                    $this->assignedTo = user()->id;
                }

                $this->projects = Project::allProjects();

                if (in_array('client', user_roles())) {
                    $this->clients = User::client();
                }
                else {
                    $this->clients = User::allClients();
                }

                $this->employees = User::allEmployees(null, true, ($viewPermission == 'all' ? 'all' : null));
                $this->taskBoardStatus = TaskboardColumn::all();
                $this->taskCategories = TaskCategory::all();
                $this->taskLabels = TaskLabelList::all();
                $this->milestones = ProjectMilestone::all();

                $taskBoardColumn = TaskboardColumn::waitingForApprovalColumn();

                $projectIds = Project::where('project_admin', user()->id)->pluck('id');

                if (!in_array('admin', user_roles()) && (in_array('employee', user_roles()) && $projectIds->isEmpty())) {
                    $user = User::findOrFail(user()->id);
                    $this->waitingApprovalCount = $user->tasks()->where('board_column_id', $taskBoardColumn->id)->where('company_id', company()->id)->count();
                }elseif(!in_array('admin', user_roles()) && (in_array('employee', user_roles()) && !$projectIds->isEmpty())) {
                    $this->waitingApprovalCount = Task::whereIn('project_id', $projectIds)->where('board_column_id', $taskBoardColumn->id)->where('company_id', company()->id)->count();
                }else{
                    $this->waitingApprovalCount = Task::where('board_column_id', $taskBoardColumn->id)->where('company_id', company()->id)->count();
                }
            }

            if($task->recurring_task_id == null){
                $redirectUrl = true;
            }

            if($redirectUrl == true){
                return Reply::successWithData(__('messages.deleteSuccess'), ['redirectUrl' => route('recurring-task.index')]);
            }
            return Reply::success(__('messages.deleteSuccess'));
        }

        return Reply::success(__('messages.deleteSuccess'));
    }

    /**
     * XXXXXXXXXXX
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->pageTitle = __('app.add') . ' ' . __('app.menu.taskRecurring');

        $this->addPermission = user()->permission('add_tasks');
        $this->projectShortCode = '';
        $this->project = request('task_project_id') ? Project::with('projectMembers')->findOrFail(request('task_project_id')) : null;

        if (is_null($this->project) || ($this->project->project_admin != user()->id)) {
            abort_403(!in_array($this->addPermission, ['all', 'added']));
        }

        $this->task = (request()['duplicate_task']) ? Task::with('users', 'label', 'project')->findOrFail(request()['duplicate_task'])->withCustomFields() : null;
        $this->selectedLabel = TaskLabel::where('task_id', request()['duplicate_task'])->get()->pluck('label_id')->toArray();
        $this->projectMember = TaskUser::where('task_id', request()['duplicate_task'])->get()->pluck('user_id')->toArray();

        $this->projects = Project::allProjects(true);

        $this->taskLabels = TaskLabelList::whereNull('project_id')->get();
        $this->projectID = request()->task_project_id;

        if (request('task_project_id')) {
            $project = Project::findOrFail(request('task_project_id'));
            $this->projectShortCode = $project->project_short_code;
            $this->taskLabels = TaskLabelList::where('project_id', request('task_project_id'))->orWhere('project_id', null)->get();
            $this->milestones = ProjectMilestone::where('project_id', request('task_project_id'))->whereNot('status', 'complete')->get();
        }
        else {
            if ($this->task && $this->task->project) {
                $this->milestones = $this->task->project->incompleteMilestones;
            }
            else {
                $this->milestones = collect([]);
            }
        }

        $this->columnId = request('column_id');
        $this->categories = TaskCategory::all();

        $this->taskboardColumns = TaskboardColumn::orderBy('priority', 'asc')->get();
        $completedTaskColumn = TaskboardColumn::where('slug', '=', 'completed')->first();

        if (request()->has('default_assign') && request('default_assign') != '') {
            $this->defaultAssignee = request('default_assign');
        }

        $this->dependantTasks = $completedTaskColumn ? Task::where('board_column_id', '<>', $completedTaskColumn->id)
            ->where('project_id', $this->projectID)
            ->whereNotNull('due_date')->get() : [];

        $this->allTasks = $completedTaskColumn ? Task::where('board_column_id', '<>', $completedTaskColumn->id)->whereNotNull('due_date')->get() : [];

        if (!is_null($this->project)) {
            if ($this->project->public) {
                $this->employees = User::allEmployees(null, true, ($this->addPermission == 'all' ? 'all' : null));

            }
            else {

                $this->employees = $this->project->projectMembers;
            }
        }
        else if (!is_null($this->task) && !is_null($this->task->project_id)) {
            if ($this->task->project->public) {
                $this->employees = User::allEmployees(null, true, ($this->addPermission == 'all' ? 'all' : null));
            }
            else {

                $this->employees = $this->task->project->projectMembers;
            }
        }
        else {
            if (in_array('client', user_roles())) {
                $this->employees = collect([]); // Do not show all employees to client

            }
            else {
                $this->employees = User::allEmployees(null, true, ($this->addPermission == 'all' ? 'all' : null));
            }

        }

        $task = new Task();

        $getCustomFieldGroupsWithFields = $task->getCustomFieldGroupsWithFields();

        if ($getCustomFieldGroupsWithFields) {
            $this->fields = $getCustomFieldGroupsWithFields->fields;
        }

        $userData = [];

        $usersData = $this->employees;

        foreach ($usersData as $user) {

            $url = route('employees.show', [$user->id]);

            $userData[] = ['id' => $user->id, 'value' => $user->name, 'image' => $user->image_url, 'link' => $url];

        }

        $this->userData = $userData;

        $this->view = 'recurring-task.ajax.create';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('recurring-task.create', $this->data);
    }

    // The function is called for duplicate code also
    public function store(StoreTask $request)
    {
        $project = request('project_id') ? Project::findOrFail(request('project_id')) : null;

        if (is_null($project) || ($project->project_admin != user()->id)) {
            $this->addPermission = user()->permission('add_tasks');
            abort_403(!in_array($this->addPermission, ['all', 'added']));
        }

        DB::beginTransaction();
        $ganttTaskArray = [];
        $gantTaskLinkArray = [];

        $taskBoardColumn = TaskboardColumn::where('slug', 'incomplete')->first();
        $task = new Task();
        $task->heading = $request->heading;
        $task->description = trim_editor($request->description);
        $dueDate = ($request->has('without_duedate')) ? null : Carbon::createFromFormat(company()->date_format, $request->due_date);
        $task->start_date = Carbon::createFromFormat(company()->date_format, $request->start_date);
        $task->due_date = $dueDate;
        $task->project_id = $request->project_id;
        $task->task_category_id = $request->category_id;
        $task->priority = $request->priority;
        $task->board_column_id = $taskBoardColumn->id;

        if ($request->has('dependent') && $request->has('dependent_task_id') && $request->dependent_task_id != '') {
            $dependentTask = Task::findOrFail($request->dependent_task_id);

            if (!is_null($dependentTask->due_date) && !is_null($dueDate) && $dependentTask->due_date->greaterThan($dueDate)) {
                /* @phpstan-ignore-line */
                return Reply::error(__('messages.taskDependentDate'));
            }

            $task->dependent_task_id = $request->dependent_task_id;
        }

        $task->is_private = $request->has('is_private') ? 1 : 0;
        $task->billable = $request->has('billable') && $request->billable ? 1 : 0;
        $task->estimate_hours = $request->estimate_hours;
        $task->estimate_minutes = $request->estimate_minutes;

        if ($request->board_column_id) {
            $task->board_column_id = $request->board_column_id;
        }

        $waitingApprovalTaskBoardColumn = TaskboardColumn::waitingForApprovalColumn();
        if($request->board_column_id == $waitingApprovalTaskBoardColumn->id){
            $task->approval_send = 1;
        }else{
            $task->approval_send = 0;
        }

        if ($request->milestone_id != '') {
            $task->milestone_id = $request->milestone_id;
        }

        // Add repeated task
        $task->repeat = $request->repeat ? 1 : 0;

        if ($request->has('repeat')) {
            $task->repeat_count = $request->repeat_count;
            $task->repeat_type = $request->repeat_type;
            $task->repeat_cycles = $request->repeat_cycles;
        }

        if ($project) {
            $projectLastTaskCount = Task::projectTaskCount($project->id);

            if (isset($project->project_short_code)) {
                $task->task_short_code = $project->project_short_code . '-' . $this->getTaskShortCode($project->project_short_code, $projectLastTaskCount);
            }
            else{
                $task->task_short_code = $projectLastTaskCount + 1;
            }
        }

        $task->save();

        // Save labels

        $task->labels()->sync($request->task_labels);


        if (!is_null($request->taskId)) {

            $taskExists = TaskFile::where('task_id', $request->taskId)->get();

            if ($taskExists) {
                foreach ($taskExists as $taskExist) {
                    $file = new TaskFile();
                    $file->user_id = $taskExist->user_id;
                    $file->task_id = $task->id;

                    $fileName = Files::generateNewFileName($taskExist->filename);

                    Files::copy(TaskFile::FILE_PATH . '/' . $taskExist->task_id . '/' . $taskExist->hashname, TaskFile::FILE_PATH . '/' . $task->id . '/' . $fileName);

                    $file->filename = $taskExist->filename;
                    $file->hashname = $fileName;
                    $file->size = $taskExist->size;
                    $file->save();


                    $this->logTaskActivity($task->id, $this->user->id, 'fileActivity', $task->board_column_id);
                }
            }


            $subTask = SubTask::with(['files'])->where('task_id', $request->taskId)->get();


            if ($subTask) {
                foreach ($subTask as $subTasks) {
                    $subTaskData = new SubTask();
                    $subTaskData->title = $subTasks->title;
                    $subTaskData->task_id = $task->id;
                    $subTaskData->description = trim_editor($subTasks->description);

                    if ($subTasks->start_date != '' && $subTasks->due_date != '') {
                        $subTaskData->start_date = $subTasks->start_date;
                        $subTaskData->due_date = $subTasks->due_date;
                    }

                    $subTaskData->assigned_to = $subTasks->assigned_to;

                    $subTaskData->save();

                    if ($subTasks->files) {
                        foreach ($subTasks->files as $fileData) {
                            $file = new SubTaskFile();
                            $file->user_id = $fileData->user_id;
                            $file->sub_task_id = $subTaskData->id;

                            $fileName = Files::generateNewFileName($fileData->filename);

                            Files::copy(SubTaskFile::FILE_PATH . '/' . $fileData->sub_task_id . '/' . $fileData->hashname, SubTaskFile::FILE_PATH . '/' . $subTaskData->id . '/' . $fileName);

                            $file->filename = $fileData->filename;
                            $file->hashname = $fileName;
                            $file->size = $fileData->size;
                            $file->save();
                        }
                    }
                }
            }
        }

        // To add custom fields data
        if ($request->custom_fields_data) {
            $task->updateCustomFieldData($request->custom_fields_data);
        }

        // For gantt chart
        if ($request->page_name && !is_null($task->due_date) && $request->page_name == 'ganttChart') {
            $task = Task::find($task->id);
            $parentGanttId = $request->parent_gantt_id;

            /* @phpstan-ignore-next-line */

            $taskDuration = $task->due_date->diffInDays($task->start_date);
            /* @phpstan-ignore-line */
            $taskDuration = $taskDuration + 1;

            $ganttTaskArray[] = [
                'id' => $task->id,
                'text' => $task->heading,
                'start_date' => $task->start_date->format('Y-m-d'), /* @phpstan-ignore-line */
                'duration' => $taskDuration,
                'parent' => $parentGanttId,
                'taskid' => $task->id
            ];

            $gantTaskLinkArray[] = [
                'id' => 'link_' . $task->id,
                'source' => $task->dependent_task_id != '' ? $task->dependent_task_id : $parentGanttId,
                'target' => $task->id,
                'type' => $task->dependent_task_id != '' ? 0 : 1
            ];
        }


        DB::commit();

        if (request()->add_more == 'true') {
            unset($request->project_id);
            $html = $this->create();

            return Reply::successWithData(__('messages.recordSaved'), ['html' => $html, 'add_more' => true, 'taskID' => $task->id]);
        }

        if ($request->page_name && $request->page_name == 'ganttChart') {

            return Reply::successWithData(
                'messages.recordSaved',
                [
                    'tasks' => $ganttTaskArray,
                    'links' => $gantTaskLinkArray
                ]
            );
        }

        $redirectUrl = urldecode($request->redirect_url);

        if ($redirectUrl == '') {
            $redirectUrl = route('recurring-task.index');
        }

        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => $redirectUrl, 'taskID' => $task->id]);

    }

    public function show($id)
    {

        $this->viewPermission = user()->permission('view_tasks');
        $viewTaskFilePermission = user()->permission('view_task_files');
        $viewSubTaskPermission = user()->permission('view_sub_tasks');
        $this->viewTaskCommentPermission = user()->permission('view_task_comments');
        $this->viewTaskNotePermission = user()->permission('view_task_notes');
        $this->viewUnassignedTasksPermission = user()->permission('view_unassigned_tasks');
        $this->viewProjectPermission = user()->permission('view_projects');
        $this->taskSettings = TaskSetting::first();

        $this->task = Task::with(
            ['boardColumn', 'project', 'users', 'label', 'approvedTimeLogs', 'mentionTask',
                'approvedTimeLogs.user', 'approvedTimeLogs.activeBreak', 'comments','activeUsers',
                'comments.commentEmoji', 'comments.like', 'comments.dislike', 'comments.likeUsers',
                'comments.dislikeUsers', 'comments.user', 'subtasks.files', 'userActiveTimer',
                'files' => function ($q) use ($viewTaskFilePermission) {
                    if ($viewTaskFilePermission == 'added') {
                        $q->where('added_by', $this->userId);
                    }
                },
                'subtasks' => function ($q) use ($viewSubTaskPermission) {
                    if ($viewSubTaskPermission == 'added') {
                        $q->where('added_by', $this->userId);
                    }
                }]
        )
            ->withCount('subtasks', 'files', 'comments', 'activeTimerAll')
            ->findOrFail($id)->withCustomFields();

        $this->userId = UserService::getUserId();
        $this->clientIds = ClientContact::where('user_id', $this->userId)->pluck('client_id')->toArray();

        $this->taskUsers = $taskUsers = $this->task->users->pluck('id')->toArray();

        $taskuserData = [];

        $usersData = $this->task->users;

        if ($this->task->createBy && !in_array($this->task->createBy->id, $taskUsers)) {
            $url = route('employees.show', [$this->task->createBy->user_id ?? $this->task->createBy->id]);
            $taskuserData[] = ['id' => $this->task->createBy->user_id ?? $this->task->createBy->id, 'value' => $this->task->createBy->user->name ?? $this->task->createBy->name, 'image' => $this->task->createBy->user->image_url ?? $this->task->createBy->image_url, 'link' => $url];
        }

        foreach ($usersData as $user) {

            $url = route('employees.show', [$user->user_id ?? $user->id]);
            $taskuserData[] = ['id' => $user->user_id ?? $user->id, 'value' => $user->user->name ?? $user->name, 'image' => $user->user->image_url ?? $user->image_url, 'link' => $url];

        }

        $this->taskuserData = $taskuserData;

        $viewTaskPermission = user()->permission('view_tasks');
        $mentionUser = $this->task->mentionTask->pluck('user_id')->toArray();

        $this->completedTaskCount = Task::where('recurring_task_id', $id)->where('status', 'completed')->count();

        $overrideViewPermission = false;
        if (request()->has('tab') && request('tab') === 'project') {
            $overrideViewPermission = true;
        }

        abort_403(
            !(
                $overrideViewPermission == true
                || $viewTaskPermission == 'all'
                || ($viewTaskPermission == 'added' && $this->task->added_by == $this->userId)
                || ($viewTaskPermission == 'owned' && in_array($this->userId, $taskUsers))
                || ($viewTaskPermission == 'both' && (in_array($this->userId, $taskUsers) || $this->task->added_by == $this->userId))
                || ($viewTaskPermission == 'owned' && in_array('client', user_roles()) && $this->task->project_id && $this->task->project->client_id == $this->userId)
                || ($viewTaskPermission == 'both' && in_array('client', user_roles()) && $this->task->project_id && $this->task->project->client_id == $this->userId)
                || ($this->viewUnassignedTasksPermission == 'all' && in_array('employee', user_roles()))
                || ($this->task->project_id && $this->task->project->project_admin == $this->userId)
                || ((!is_null($this->task->mentionTask)) && in_array($this->userId, $mentionUser))
            )

        );

        if (!$this->task->project_id || ($this->task->project_id && $this->task->project->project_admin != $this->userId)) {

            abort_403($this->viewUnassignedTasksPermission == 'none' && count($taskUsers) == 0 && ((is_null($this->task->mentionTask)) && in_array($this->userId, $mentionUser)));

        }

        $this->pageTitle = __('app.task') . ' ' . __('app.details');
        $tab = request('tab');

        switch ($tab) {
        case 'task':
                return $this->tasks($id);
        default:
            $this->view = 'recurring-task.ajax.show';
            break;
        }

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        $this->activeTab = $tab ?: 'overview';

        return view('recurring-task.show', $this->data);
    }

    public function tasks($recurringID)
    {
        $dataTable = new TasksDataTable();
        $viewPermission = user()->permission('view_tasks');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        $this->recurringID = $recurringID;
        $this->task = Task::findOrFail($recurringID);
        $this->recurringTasks = Task::where('id', $recurringID)->orWhere('recurring_task_id', $recurringID)->get();
        $this->taskBoardStatus = TaskboardColumn::all();

        $this->currentYear = now()->format('Y');
        $this->currentMonth = now()->month;

        /* year range from last 5 year to next year */
        $years = [];

        $latestFifthYear = (int)now()->subYears(5)->format('Y');
        $nextYear = (int)now()->addYear()->format('Y');

        for ($i = $latestFifthYear; $i <= $nextYear; $i++) {
            $years[] = $i;
        }

        $this->years = $years;

        $tab = request('tab');
        $this->activeTab = $tab ?: 'overview';

        $this->view = 'recurring-task.ajax.task';

        return $dataTable->render('recurring-task.show', $this->data);
    }

    public function update(UpdateTask $request, $id)
    {
        $task = Task::with('users', 'label', 'project')->findOrFail($id)->withCustomFields();
        $editTaskPermission = user()->permission('edit_tasks');
        $taskUsers = $task->users->pluck('id')->toArray();

        abort_403(
            !($editTaskPermission == 'all'
                || ($editTaskPermission == 'owned' && in_array(user()->id, $taskUsers))
                || ($editTaskPermission == 'added' && $task->added_by == user()->id)
                || ($task->project && ($task->project->project_admin == user()->id))
                || ($editTaskPermission == 'both' && (in_array(user()->id, $taskUsers) || $task->added_by == user()->id))
                || ($editTaskPermission == 'owned' && (in_array('client', user_roles()) && $task->project && ($task->project->client_id == user()->id)))
                || ($editTaskPermission == 'both' && (in_array('client', user_roles()) && ($task->project && ($task->project->client_id == user()->id)) || $task->added_by == user()->id))
            )
        );

        $dueDate = ($request->has('without_duedate')) ? null : Carbon::createFromFormat(company()->date_format, $request->due_date);
        $task->heading = $request->heading;
        $task->description = trim_editor($request->description);
        $task->start_date = Carbon::createFromFormat(company()->date_format, $request->start_date);
        $task->due_date = $dueDate;
        $task->task_category_id = $request->category_id;
        $task->priority = $request->priority;


        if ($request->has('board_column_id')) {

            $task->board_column_id = $request->board_column_id;
            $task->approval_send = 0;
            $taskBoardColumn = TaskboardColumn::findOrFail($request->board_column_id);

            if ($taskBoardColumn->slug == 'completed') {
                $task->completed_on = now()->format('Y-m-d');
            }
            else {
                $task->completed_on = null;
            }
        }

        if($request->select_value == 'Waiting Approval'){

            $taskBoardColumn = TaskboardColumn::where('column_name', $request->select_value)->where('company_id', company()->id)->first();
            $task->board_column_id = $taskBoardColumn->id;
            $task->approval_send = 1;
        }

        $task->dependent_task_id = $request->has('dependent') && $request->has('dependent_task_id') && $request->dependent_task_id != '' ? $request->dependent_task_id : null;
        $task->is_private = $request->has('is_private') ? 1 : 0;
        $task->billable = $request->has('billable') && $request->billable ? 1 : 0;
        $task->estimate_hours = $request->estimate_hours;
        $task->estimate_minutes = $request->estimate_minutes;

        if ($request->project_id != '') {
            $task->project_id = $request->project_id;
            ProjectTimeLog::where('task_id', $id)->update(['project_id' => $request->project_id]);
        }
        else {
            $task->project_id = null;
        }

        if ($request->has('milestone_id')) {
            $task->milestone_id = $request->milestone_id;
        }

        if ($request->has('dependent') && $request->has('dependent_task_id') && $request->dependent_task_id != '') {
            $dependentTask = Task::findOrFail($request->dependent_task_id);

            if (!is_null($dependentTask->due_date) && !is_null($dueDate) && $dependentTask->due_date->greaterThan($dueDate)) {
                return Reply::error(__('messages.taskDependentDate'));
            }

            $task->dependent_task_id = $request->dependent_task_id;
        }

        // Add repeated task
        $task->repeat = $request->repeat ? 1 : 0;

        if ($request->has('repeat')) {
            $task->repeat_count = $request->repeat_count;
            $task->repeat_type = $request->repeat_type;
            $task->repeat_cycles = $request->repeat_cycles;
        }

        $task->load('project');

        $project = $task->project;

        if ($project && $task->isDirty('project_id')) {
            $projectLastTaskCount = Task::projectTaskCount($project->id);
            $task->task_short_code = $project->project_short_code . '-' . $this->getTaskShortCode($project->project_short_code, $projectLastTaskCount);
        }
        $task->save();

        // save labels
        $task->labels()->sync($request->task_labels);

        // To add custom fields data
        if ($request->custom_fields_data) {
            $task->updateCustomFieldData($request->custom_fields_data);
        }

        // Sync task users
        $task->users()->sync($request->user_id);

        if(!empty($request->user_id)){
            $newlyAssignedUserIds = array_diff($request->user_id, $taskUsers);
            if (!empty($newlyAssignedUserIds)) {
                $newUsers = User::whereIn('id', $newlyAssignedUserIds)->get();
                event(new TaskEvent($task, $newUsers, 'NewTask'));
            }
        }

        return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => route('recurring-task.show', $id)]);
    }

    public function getTaskShortCode($projectShortCode, $lastProjectCount)
    {
        $task = Task::where('task_short_code', $projectShortCode . '-' . $lastProjectCount)->exists();

        if ($task) {
            return $this->getTaskShortCode($projectShortCode, $lastProjectCount + 1);
        }

        return $lastProjectCount;

    }

}
