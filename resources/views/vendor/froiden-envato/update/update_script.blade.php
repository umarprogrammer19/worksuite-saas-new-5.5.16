<script type="text/javascript">
    let updateAreaDiv = $('#update-area');
    let refreshPercent = 0;
    let checkInstall = true;
    let checkExtractInterval = null;
    let installTimeout = null;
    const MAX_INSTALL_CHECK_TIME = 10 * 60 * 1000; // 10 minutes max

    $('#update-app').click(function () {
        if ($('#update-frame').length) {
            return false;
        }
        @php($envatoUpdateCompanySetting = \Froiden\Envato\Functions\EnvatoUpdate::companySetting())

        @if(!is_null($envatoUpdateCompanySetting->supported_until) && \Carbon\Carbon::parse($envatoUpdateCompanySetting->supported_until)->isPast())
        let supportText = " Your support has been expired on <b><span id='support-date'>{{\Carbon\Carbon::parse($envatoUpdateCompanySetting->supported_until)->translatedFormat('dS M, Y')}}</span></b>";

        Swal.fire({
            title: "Support Expired",
            html: supportText + "<br>Please renew your support for one-click updates.<br><br> You can still update the application manually by following the documentation <a href='https://froiden.freshdesk.com/support/solutions/articles/43000554421-update-application-manually' target='_blank'>Update Application Manually</a>",
            showCancelButton: true,
            confirmButtonText: "Renew Now",
            denyButtonText: `Free Support Guidelines`,
            cancelButtonText: "Cancel",
            closeOnConfirm: true,
            closeOnCancel: true,
            showCloseButton: true,
            icon: 'warning',
            focusConfirm: false,
            customClass: {
                confirmButton: 'btn btn-primary mr-3',
                denyButton: 'btn btn-success mr-3 p-2',
                cancelButton: 'btn btn-secondary'
            },
            showClass: {
                popup: 'swal2-noanimation',
                backdrop: 'swal2-noanimation'
            },
            buttonsStyling: false,
        }).then((result) => {
            if (result.isConfirmed) {
                window.open(
                    "{{ config('froiden_envato.envato_product_url') }}",
                    '_blank'
                );
            }
        });


        @else

        Swal.fire({

            title: "Are you sure?",
            html: `<x-alert type="danger" icon="info-circle">Please do not click the <strong>Yes! Update It</strong> button if the application has been customized. Your changes may be lost.\n
                <br>
                <br>
                As a precautionary measure, please make a backup of your files and database before updating.. \
                <br>
                <br>
                <strong class="mt-2"><i>Please note that the author will not be held responsible for any loss of data or issues that may occur during the update process.</i></strong>
                </x-alert>
                <span class="">To confirm if you have read the above message, type <strong><i>confirm</i></strong> in the field.</span>
                `,
            icon: 'info',
            focusConfirm: true,
            customClass: {
                confirmButton: 'btn btn-primary mr-3',
                cancelButton: 'btn btn-secondary'
            },
            showClass: {
                popup: 'swal2-noanimation',
                backdrop: 'swal2-noanimation'
            },
            buttonsStyling: false,
            input: 'text',
            inputAttributes: {
                autocapitalize: 'off'
            },
            showCloseButton: true,
            showCancelButton: true,
            confirmButtonText: "Yes, update it!",
            cancelButtonText: "No, cancel please!",
            padding: '3em',
            showLoaderOnConfirm: true,
            preConfirm: (isConfirm) => {

                if (!isConfirm) {
                    return false;
                }

                if (isConfirm.toLowerCase() !== "confirm") {

                    Swal.fire({
                        title: "Text not matched",
                        html: "You have entered wrong spelling of <b>confirm</b>",
                        icon: 'error',
                    });
                    return false;
                }
                if (isConfirm.toLowerCase() === "confirm") {
                    return true;
                }
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                updateAreaDiv.removeClass('d-none');
                Swal.close();
                $.easyAjax({
                    type: 'GET',
                    blockUI: true,
                    url: '{!! route("admin.updateVersion.update") !!}',
                    success: function (response) {
                        if (response.status === 'success') {
                            updateAreaDiv.html("<strong>Downloading...:-</strong><br> ");
                            downloadScript();
                            downloadPercent();
                        } else if (response.status === 'fail')
                            updateAreaDiv.addClass('d-none');
                    }
                });
            }
        });
        @endif


    })

    function downloadScript() {
        $.easyAjax({
            type: 'GET',
            url: '{!! route("admin.updateVersion.download") !!}',
            success: function (response) {
                clearInterval(refreshPercent);

                if(response.status === 'fail'){
                    updateAreaDiv.html(`<i><span class='text-red'><strong>Update Failed</strong> :</span> ${response.message}</i>`)
                    return false;
                }

                $('#percent-complete').css('width', '100%');
                $('#percent-complete').html('100%');
                $('#download-progress').append("<i><span class='text-success'>Download complete.</span> Now Installing...Please wait (This may take few minutes.)</i>");

                // Clear any existing interval and timeout first
                if (checkExtractInterval) {
                    clearInterval(checkExtractInterval);
                }
                if (installTimeout) {
                    clearTimeout(installTimeout);
                }

                // Set timeout to prevent infinite checking
                installTimeout = setTimeout(function() {
                    if (checkInstall) {
                        checkInstall = false;
                        if (checkExtractInterval) {
                            clearInterval(checkExtractInterval);
                        }
                        updateAreaDiv.html(`<i><span class='text-red'><strong>Installation Timeout</strong> :</span> Installation is taking longer than expected. Please check the server logs or try refreshing the page.</i>`);
                    }
                }, MAX_INSTALL_CHECK_TIME);

                // Start checking if file is extracted
                checkExtractInterval = window.setInterval(function () {
                    if (checkInstall == true) {
                        checkIfFileExtracted();
                    } else {
                        clearInterval(checkExtractInterval);
                        if (installTimeout) {
                            clearTimeout(installTimeout);
                        }
                    }
                }, 1500);

                // Start installation
                installScript();

            },
            error: function(xhr, status, error) {
                clearInterval(refreshPercent);
                if (checkExtractInterval) {
                    clearInterval(checkExtractInterval);
                }
                if (installTimeout) {
                    clearTimeout(installTimeout);
                }

                // Extract detailed error information
                let errorMessage = 'An error occurred during download';
                let errorDetails = [];

                // Try to get error message from response
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            errorMessage = response.message;
                        }
                    } catch (e) {
                        // If not JSON, use responseText if it's not too long
                        if (xhr.responseText && xhr.responseText.length < 500) {
                            errorMessage = xhr.responseText;
                        }
                    }
                }

                // Add HTTP status information
                if (xhr.status) {
                    errorDetails.push(`HTTP Status: ${xhr.status} ${xhr.statusText || ''}`);
                }

                // Add error type
                if (status) {
                    errorDetails.push(`Error Type: ${status}`);
                }

                // Build final error message
                let finalErrorMessage = `<strong>Download Failed</strong><br>${errorMessage}`;
                if (errorDetails.length > 0) {
                    finalErrorMessage += `<br><small>${errorDetails.join(' | ')}</small>`;
                }

                updateAreaDiv.html(`<i><span class='text-red'>${finalErrorMessage}</span></i>`);
            }
        });
    }

    function getDownloadPercent() {
        $.easyAjax({
            type: 'GET',
            url: '{!! route("admin.updateVersion.downloadPercent") !!}',
            success: function (response) {
                response = response.toFixed(1);
                $('#percent-complete').css('width', response + '%');
                $('#percent-complete').html(response + '%');
            }
        });
    }

    function checkIfFileExtracted() {
        $.easyAjax({
            type: 'GET',
            url: '{!! route("admin.updateVersion.checkIfFileExtracted") !!}',
            success: function (response) {
                if (response.status == 'success') {
                    // Only stop checking when extraction is actually successful
                    checkInstall = false;
                    if (checkExtractInterval) {
                        clearInterval(checkExtractInterval);
                    }
                    if (installTimeout) {
                        clearTimeout(installTimeout);
                    }
                    window.location.reload();
                }
                // If status is not 'success', keep checking (don't set checkInstall = false)
            },
            error: function(xhr, status, error) {
                // On error, keep checking - don't stop the interval
                console.error('Error checking file extraction:', error);
            }
        });
    }

    function downloadPercent() {
        updateAreaDiv.append('<hr><div id="download-progress">' +
            'Download Progress<br><div class="progress progress-lg">' +
            '<div class="progress-bar progress-bar-success active progress-bar-striped" id="percent-complete" role="progressbar""></div>' +
            '</div>' +
            '</div>'
        );
        //getting data
        refreshPercent = window.setInterval(function () {
            getDownloadPercent();
            /// call your function here
        }, 1500);
    }

    function installScript() {
        $.easyAjax({
            type: 'GET',
            url: '{!! route("admin.updateVersion.install") !!}',
            success: function (response) {
                if (response.status == 'success') {
                    // Installation started successfully, checkIfFileExtracted will handle the reload
                    // Don't reload here to avoid race condition with checkIfFileExtracted
                } else if (response.status == 'fail') {
                    // Installation failed
                    checkInstall = false;
                    if (checkExtractInterval) {
                        clearInterval(checkExtractInterval);
                    }
                    if (installTimeout) {
                        clearTimeout(installTimeout);
                    }
                    updateAreaDiv.html(`<i><span class='text-red'><strong>Installation Failed</strong> :</span> ${response.message || 'Installation process failed'}</i>`);
                }
            },
            error: function(xhr, status, error) {
                // Installation request failed
                checkInstall = false;
                if (checkExtractInterval) {
                    clearInterval(checkExtractInterval);
                }
                if (installTimeout) {
                    clearTimeout(installTimeout);
                }

                // Extract detailed error information
                let errorMessage = 'An error occurred during installation';
                let errorDetails = [];

                // Try to get error message from response
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            errorMessage = response.message;
                        }
                    } catch (e) {
                        // If not JSON, use responseText if it's not too long
                        if (xhr.responseText && xhr.responseText.length < 500) {
                            errorMessage = xhr.responseText;
                        }
                    }
                }

                // Add HTTP status information
                if (xhr.status) {
                    errorDetails.push(`HTTP Status: ${xhr.status} ${xhr.statusText || ''}`);
                }

                // Add error type
                if (status) {
                    errorDetails.push(`Error Type: ${status}`);
                }

                // Build final error message
                let finalErrorMessage = `<strong>Installation Failed</strong><br>${errorMessage}`;
                if (errorDetails.length > 0) {
                    finalErrorMessage += `<br><small>${errorDetails.join(' | ')}</small>`;
                }

                updateAreaDiv.html(`<i><span class='text-red'>${finalErrorMessage}</span></i>`);
            }
        });
    }

    function getPurchaseData() {
        const token = "{{ csrf_token() }}";
        $.easyAjax({
            type: 'POST',
            url: "{{ route('purchase-verified') }}",
            data: {'_token': token},
            container: "#support-div",
            messagePosition: 'inline',
            success: function (response) {
                window.location.reload();
            }
        });
        return false;
    }

    function showHidePurchaseCode() {
        $(this).toggleClass('fa-eye-slash fa-eye');
        $(this).siblings('span').toggleClass('blur-code ');
    }
    $("body").tooltip({
        selector: '[data-toggle="tooltip"]'
    })

</script>
