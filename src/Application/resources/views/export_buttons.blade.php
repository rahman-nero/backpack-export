@if ($crud->exportButtons())
    <script src="https://cdn.datatables.net/buttons/1.5.6/js/dataTables.buttons.min.js" type="text/javascript"></script>
    <script src="https://cdn.datatables.net/buttons/1.5.6/js/buttons.bootstrap4.min.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.18/vfs_fonts.js" type="text/javascript"></script>
    <script src="https://cdn.datatables.net/buttons/1.5.6/js/buttons.html5.min.js" type="text/javascript"></script>
    <script src="https://cdn.datatables.net/buttons/1.5.6/js/buttons.colVis.min.js" type="text/javascript"></script>

    <script>
        let dataTablesExportStrip = text => {
            if (typeof text !== 'string') {
                return text;
            }

            return text
                .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
                .replace(/<!\-\-.*?\-\->/g, '')
                .replace(/<[^>]*>/g, '')
                .replace(/^\s+|\s+$/g, '')
                .replace(/\s+([,.;:!\?])/g, '$1')
                .replace(/\s+/g, ' ')
                .replace(/[\n|\r]/g, ' ');
        };

        let dataTablesExportFormat = {
            body: (data, row, column, node) =>
                node.querySelector('input[type*="text"]')?.value ??
                node.querySelector('input[type*="checkbox"]:not(.crud_bulk_actions_line_checkbox)')?.checked ??
                node.querySelector('select')?.selectedOptions[0]?.value ??
                dataTablesExportStrip(data),
        };


        window.crud.dataTableConfiguration.buttons = [
                @if($crud->get('list.showExportButton') || $crud->get('list.advancedExportButtons'))
            {
                extend: 'collection',
                text: '<i class="la la-download"></i> {{ trans('backpack::crud.export.export') }}',
                dropup: true,
                buttons: [
                    {
                        name: 'copyHtml5',
                        extend: 'copyHtml5',
                        exportOptions: {
                            columns: function (idx, data, node) {
                                var $column = crud.table.column(idx);
                                return ($column.visible() && $(node).attr('data-visible-in-export') != 'false') || $(node).attr('data-force-export') == 'true';
                            }
                        },
                        action: function (e, dt, button, config) {
                            crud.responsiveToggle(dt);
                            $.fn.DataTable.ext.buttons.copyHtml5.action.call(this, e, dt, button, config);
                            crud.responsiveToggle(dt);
                        }
                    },
                    {
                        name: 'excelHtml5',
                        extend: 'excelHtml5',
                        exportOptions: {
                            columns: function (idx, data, node) {
                                var $column = crud.table.column(idx);
                                return ($column.visible() && $(node).attr('data-visible-in-export') != 'false') || $(node).attr('data-force-export') == 'true';
                            }
                        },
                        action: function (e, dt, button, config) {
                            crud.responsiveToggle(dt);
                            @if($crud->get('list.advancedExportButtons'))
                                execute("excel");
                            @else
                                $.fn.DataTable.ext.buttons.excelHtml5.action.call(this, e, dt, button, config);
                            @endif
                            crud.responsiveToggle(dt);
                        }
                    },
                    {
                        name: 'csvHtml5',
                        extend: 'csvHtml5',
                        exportOptions: {
                            columns: function (idx, data, node) {
                                var $column = crud.table.column(idx);
                                return ($column.visible() && $(node).attr('data-visible-in-export') != 'false') || $(node).attr('data-force-export') == 'true';
                            }
                        },
                        action: function (e, dt, button, config) {
                            crud.responsiveToggle(dt);
                            @if($crud->get('list.advancedExportButtons'))
                                execute("csv");
                            @else
                                $.fn.DataTable.ext.buttons.csvHtml5.action.call(this, e, dt, button, config);
                            @endif
                            crud.responsiveToggle(dt);
                        }
                    },
                        // Has not been implemented yet
                        @if(!$crud->get('list.advancedExportButtons'))
                    {
                        name: 'pdfHtml5',
                        extend: 'pdfHtml5',
                        exportOptions: {
                            columns: function (idx, data, node) {
                                var $column = crud.table.column(idx);
                                return ($column.visible() && $(node).attr('data-visible-in-export') != 'false') || $(node).attr('data-force-export') == 'true';
                            }
                        },
                        orientation: 'landscape',
                        action: function (e, dt, button, config) {
                            crud.responsiveToggle(dt);
                            $.fn.DataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, button, config);
                            crud.responsiveToggle(dt);
                        }
                    },
                        @endif
                    {
                        name: 'print',
                        extend: 'print',
                        exportOptions: {
                            columns: function (idx, data, node) {
                                var $column = crud.table.column(idx);
                                return ($column.visible() && $(node).attr('data-visible-in-export') != 'false') || $(node).attr('data-force-export') == 'true';
                            }
                        },
                        action: function (e, dt, button, config) {
                            crud.responsiveToggle(dt);
                            $.fn.DataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                            crud.responsiveToggle(dt);
                        }
                    }
                ]


            }
            @endif
            @if($crud->get('list.showTableColumnPicker'))
            , {
                extend: 'colvis',
                text: '<i class="la la-eye-slash"></i> {{ trans('backpack::crud.export.column_visibility') }}',
                columns: function (idx, data, node) {
                    return $(node).attr('data-visible-in-table') == 'false' && $(node).attr('data-can-be-visible-in-table') == 'true';
                },
                dropup: true
            }
            @endif
        ];


        // Request a export
        function execute(type) {
            const csrf_token = document.head.querySelector('meta[name="csrf-token"]').content;
            const uri = window.location.pathname;
            const data = {
                export_type: type,
            };

            // Create a URLSearchParams object
            const searchParams = new URL(window.location.href).searchParams;

            // Fill data with params
            for (const [key, value] of searchParams.entries()) {
                data[key] = value;
            }

            fetch(`${uri}/export`, {
                method: "POST",
                body: JSON.stringify(data),
                headers: {
                    "X-CSRF-TOKEN": csrf_token,
                    "Content-Type": "application/json",
                }
            })
                .then(response => response.json())
                .then(data => {
                    new Noty({
                        type: "success",
                        text: data.message,
                    }).show();
                });
        }


        // move the datatable buttons in the top-right corner and make them smaller
        function moveExportButtonsToTopRight() {
            crud.table.buttons().each(function (button) {
                if (button.node.className.indexOf('buttons-columnVisibility') == -1 && button.node.nodeName == 'BUTTON') {
                    button.node.className = button.node.className + " btn-sm";
                }
            })
            $(".dt-buttons").appendTo($('#datatable_button_stack'));
            $('.dt-buttons').addClass('d-xs-block')
                .addClass('d-sm-inline-block')
                .addClass('d-md-inline-block')
                .addClass('d-lg-inline-block');
        }

        crud.addFunctionToDataTablesDrawEventQueue('moveExportButtonsToTopRight');
    </script>
@endif
