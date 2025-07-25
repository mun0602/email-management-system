// Main JavaScript functions for Mail Management System
$(document).ready(function() {
    // Copy to clipboard functionality
    $('.btn-copy').on('click', function() {
        const target = $(this).data('target');
        const text = $(target).text();
        
        navigator.clipboard.writeText(text).then(function() {
            // Show success feedback
            const btn = $(this);
            const originalText = btn.html();
            btn.html('<i class="bi bi-check"></i> Đã sao chép');
            btn.addClass('btn-success').removeClass('btn-outline-primary');
            
            setTimeout(function() {
                btn.html(originalText);
                btn.removeClass('btn-success').addClass('btn-outline-primary');
            }, 2000);
        }.bind(this));
    });
    
    // App selection change handler
    $('#app_select').on('change', function() {
        const customInput = $('#custom_app_input');
        if ($(this).val() === 'other') {
            customInput.show().prop('required', true);
        } else {
            customInput.hide().prop('required', false).val('');
        }
    });
    
    // Bulk mail textarea auto-resize
    $('#bulk_mails').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    // Select all checkboxes
    $('#selectAll').on('change', function() {
        $('.mail-checkbox').prop('checked', $(this).prop('checked'));
        updateShareButtonState();
    });
    
    // Individual checkbox change
    $('.mail-checkbox').on('change', function() {
        updateShareButtonState();
        updateSelectAllState();
    });
    
    // Update share button state
    function updateShareButtonState() {
        const checkedBoxes = $('.mail-checkbox:checked').length;
        $('#shareSelected').prop('disabled', checkedBoxes === 0);
        $('#shareSelected .badge').text(checkedBoxes);
    }
    
    // Update select all checkbox state
    function updateSelectAllState() {
        const totalBoxes = $('.mail-checkbox').length;
        const checkedBoxes = $('.mail-checkbox:checked').length;
        $('#selectAll').prop('checked', totalBoxes === checkedBoxes);
    }
    
    // Share modal form submission
    $('#shareForm').on('submit', function(e) {
        e.preventDefault();
        
        const selectedMails = [];
        $('.mail-checkbox:checked').each(function() {
            selectedMails.push($(this).val());
        });
        
        if (selectedMails.length === 0) {
            alert('Vui lòng chọn ít nhất một mail để chia sẻ!');
            return;
        }
        
        const formData = {
            mail_ids: selectedMails,
            password: $('#share_password').val(),
            action: 'create_share'
        };
        
        $.post('/shared/create.php', formData, function(response) {
            if (response.success) {
                $('#shareLink').val(response.share_url);
                $('#shareLinkModal').modal('show');
                $('#shareModal').modal('hide');
            } else {
                alert('Có lỗi xảy ra: ' + response.message);
            }
        }, 'json');
    });
    
    // Copy share link
    $('#copyShareLink').on('click', function() {
        const shareLink = $('#shareLink')[0];
        shareLink.select();
        document.execCommand('copy');
        
        $(this).html('<i class="bi bi-check"></i> Đã sao chép');
        setTimeout(() => {
            $(this).html('<i class="bi bi-clipboard"></i> Sao chép link');
        }, 2000);
    });
    
    // Search and filter functionality
    $('#searchInput').on('keyup', function() {
        const value = $(this).val().toLowerCase();
        $('.searchable-row').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
    
    // Date range filtering
    $('#dateFrom, #dateTo').on('change', function() {
        filterByDate();
    });
    
    function filterByDate() {
        const fromDate = $('#dateFrom').val();
        const toDate = $('#dateTo').val();
        
        if (!fromDate && !toDate) {
            $('.searchable-row').show();
            return;
        }
        
        $('.searchable-row').each(function() {
            const rowDate = $(this).data('date');
            let show = true;
            
            if (fromDate && rowDate < fromDate) show = false;
            if (toDate && rowDate > toDate) show = false;
            
            $(this).toggle(show);
        });
    }
    
    // Confirmation dialogs
    $('.btn-delete').on('click', function(e) {
        if (!confirm('Bạn có chắc chắn muốn xóa không?')) {
            e.preventDefault();
        }
    });
    
    // Auto-hide alerts
    $('.alert').delay(5000).fadeOut();
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Chart creation helpers
function createPieChart(canvasId, data, labels) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    return new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    '#007bff', '#28a745', '#ffc107', '#dc3545', 
                    '#6f42c1', '#fd7e14', '#20c997', '#6c757d'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function createBarChart(canvasId, data, labels) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Số lượng mail',
                data: data,
                backgroundColor: '#007bff',
                borderColor: '#0056b3',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}