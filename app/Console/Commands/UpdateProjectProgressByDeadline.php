<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Traits\ProjectProgress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateProjectProgressByDeadline extends Command
{
    use ProjectProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects-update-deadline-progress';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update project progress for projects using deadline-based calculation';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting project progress update for deadline-based projects...');

        // Get all projects that use deadline-based progress calculation
        $projects = Project::where('calculate_task_progress', 'project_deadline')
            ->whereNotNull('start_date')
            ->whereNotNull('deadline')
            ->get();

        $updatedCount = 0;
        $errorCount = 0;

        foreach ($projects as $project) {
            try {
                $oldProgress = $project->completion_percent;
                $oldStatus = $project->status;

                // Calculate and update progress
                $newProgress = $this->calculateProjectProgressByDeadline($project->id);

                if ($newProgress !== false) {
                    // Reload the project to get updated values
                    $project->refresh();
                    
                    $newStatus = $project->status;
                    
                    // Log significant changes
                    if ($oldProgress != $newProgress || $oldStatus != $newStatus) {
                        Log::info("Project progress updated", [
                            'project_id' => $project->id,
                            'project_name' => $project->project_name,
                            'old_progress' => $oldProgress,
                            'new_progress' => $newProgress,
                            'old_status' => $oldStatus,
                            'new_status' => $newStatus,
                            'deadline' => $project->deadline->format('Y-m-d')
                        ]);
                        
                        $this->line("Updated project: {$project->project_name} - Progress: {$oldProgress}% → {$newProgress}%");
                        
                        if ($oldStatus != $newStatus) {
                            $this->line("  Status changed: {$oldStatus} → {$newStatus}");
                        }
                    }
                    
                    $updatedCount++;
                } else {
                    $this->warn("Failed to calculate progress for project: {$project->project_name} (ID: {$project->id})");
                    $errorCount++;
                }
            } catch (\Exception $e) {
                Log::error("Error updating project progress", [
                    'project_id' => $project->id,
                    'project_name' => $project->project_name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                $this->error("Error updating project {$project->project_name}: " . $e->getMessage());
                $errorCount++;
            }
        }

        $this->info("Project progress update completed.");
        $this->info("Projects processed: " . $projects->count());
        $this->info("Successfully updated: {$updatedCount}");
        
        if ($errorCount > 0) {
            $this->warn("Errors encountered: {$errorCount}");
        }

        // Log summary
        Log::info("Daily project progress update completed", [
            'total_projects' => $projects->count(),
            'updated_count' => $updatedCount,
            'error_count' => $errorCount,
            'execution_time' => now()->toDateTimeString()
        ]);

        return Command::SUCCESS;
    }
}
