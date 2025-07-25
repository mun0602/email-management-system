// Main JavaScript functions for Mail Management System (Vanilla JS)
document.addEventListener('DOMContentLoaded', function() {
    // Copy to clipboard functionality
    function setupCopyButtons() {
        document.querySelectorAll('.btn-copy').forEach(button => {
            button.addEventListener('click', function() {
                const target = this.getAttribute('data-target');
                const targetElement = document.querySelector(target);
                if (!targetElement) return;
                
                const text = targetElement.textContent;
                
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(() => {
                        showCopySuccess(this);
                    }).catch(() => {
                        fallbackCopyToClipboard(text, this);
                    });
                } else {
                    fallbackCopyToClipboard(text, this);
                }
            });
        });
    }
    
    function showCopySuccess(button) {
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="bi bi-check"></i> Đã sao chép';
        button.classList.add('btn-success');
        button.classList.remove('btn-outline-primary');
        
        setTimeout(() => {
            button.innerHTML = originalHTML;
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-primary');
        }, 2000);
    }
    
    function fallbackCopyToClipboard(text, button) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            showCopySuccess(button);
        } catch (err) {
            console.error('Fallback: Oops, unable to copy', err);
        }
        document.body.removeChild(textArea);
    }
    
    // App selection change handler
    const appSelect = document.getElementById('app_name');
    const customAppGroup = document.getElementById('custom_app_group');
    const customAppInput = document.getElementById('custom_app');
    
    if (appSelect && customAppGroup) {
        appSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                customAppGroup.style.display = 'block';
                if (customAppInput) customAppInput.required = true;
            } else {
                customAppGroup.style.display = 'none';
                if (customAppInput) {
                    customAppInput.required = false;
                    customAppInput.value = '';
                }
            }
        });
    }
    
    // Bulk mail textarea auto-resize
    const bulkMailsTextarea = document.getElementById('bulk_mails');
    if (bulkMailsTextarea) {
        bulkMailsTextarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }
    
    // Select all checkboxes functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const mailCheckboxes = document.querySelectorAll('.mail-checkbox');
            mailCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateShareButtonState();
        });
    }
    
    // Individual checkbox change
    document.querySelectorAll('.mail-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateShareButtonState();
            updateSelectAllState();
        });
    });
    
    // Update share button state
    function updateShareButtonState() {
        const checkedBoxes = document.querySelectorAll('.mail-checkbox:checked').length;
        const shareButton = document.getElementById('shareSelected');
        if (shareButton) {
            shareButton.disabled = checkedBoxes === 0;
            const badge = shareButton.querySelector('.badge');
            if (badge) badge.textContent = checkedBoxes;
        }
    }
    
    // Update select all checkbox state
    function updateSelectAllState() {
        const totalBoxes = document.querySelectorAll('.mail-checkbox').length;
        const checkedBoxes = document.querySelectorAll('.mail-checkbox:checked').length;
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.checked = totalBoxes === checkedBoxes;
        }
    }
    
    // Share form submission
    const shareForm = document.getElementById('shareForm');
    if (shareForm) {
        shareForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const selectedMails = [];
            document.querySelectorAll('.mail-checkbox:checked').forEach(checkbox => {
                selectedMails.push(checkbox.value);
            });
            
            if (selectedMails.length === 0) {
                alert('Vui lòng chọn ít nhất một mail để chia sẻ!');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'create_share');
            formData.append('password', document.getElementById('share_password').value);
            
            // Append mail IDs as individual form fields
            selectedMails.forEach((mailId, index) => {
                formData.append(`mail_ids[${index}]`, mailId);
            });
            
            fetch('/shared/create.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('shareLink').value = data.share_url;
                    // Close share modal and open share link modal
                    if (typeof bootstrap !== 'undefined') {
                        const shareModal = bootstrap.Modal.getInstance(document.getElementById('shareModal'));
                        const shareLinkModal = new bootstrap.Modal(document.getElementById('shareLinkModal'));
                        if (shareModal) shareModal.hide();
                        shareLinkModal.show();
                    }
                } else {
                    alert('Có lỗi xảy ra: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi tạo link chia sẻ');
            });
        });
    }
    
    // Copy share link
    const copyShareLinkBtn = document.getElementById('copyShareLink');
    if (copyShareLinkBtn) {
        copyShareLinkBtn.addEventListener('click', function() {
            const shareLink = document.getElementById('shareLink');
            if (shareLink) {
                shareLink.select();
                shareLink.setSelectionRange(0, 99999); // For mobile devices
                
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(shareLink.value).then(() => {
                        this.innerHTML = '<i class="bi bi-check"></i> Đã sao chép';
                        setTimeout(() => {
                            this.innerHTML = '<i class="bi bi-clipboard"></i> Sao chép link';
                        }, 2000);
                    });
                } else {
                    document.execCommand('copy');
                    this.innerHTML = '<i class="bi bi-check"></i> Đã sao chép';
                    setTimeout(() => {
                        this.innerHTML = '<i class="bi bi-clipboard"></i> Sao chép link';
                    }, 2000);
                }
            }
        });
    }
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const value = this.value.toLowerCase();
            const searchableRows = document.querySelectorAll('.searchable-row');
            searchableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.indexOf(value) > -1 ? '' : 'none';
            });
        });
    }
    
    // Date range filtering
    const dateFrom = document.getElementById('dateFrom');
    const dateTo = document.getElementById('dateTo');
    
    if (dateFrom || dateTo) {
        [dateFrom, dateTo].forEach(input => {
            if (input) {
                input.addEventListener('change', filterByDate);
            }
        });
    }
    
    function filterByDate() {
        const fromValue = dateFrom ? dateFrom.value : '';
        const toValue = dateTo ? dateTo.value : '';
        
        if (!fromValue && !toValue) {
            document.querySelectorAll('.searchable-row').forEach(row => {
                row.style.display = '';
            });
            return;
        }
        
        document.querySelectorAll('.searchable-row').forEach(row => {
            const rowDate = row.getAttribute('data-date');
            let show = true;
            
            if (fromValue && rowDate < fromValue) show = false;
            if (toValue && rowDate > toValue) show = false;
            
            row.style.display = show ? '' : 'none';
        });
    }
    
    // Confirmation dialogs
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Bạn có chắc chắn muốn xóa không?')) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-hide alerts
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 500);
        }, 5000);
    });
    
    // Initialize tooltips (Bootstrap 5)
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => 
            new bootstrap.Tooltip(tooltipTriggerEl)
        );
    }
    
    // Initialize copy buttons
    setupCopyButtons();
    
    // Initialize other functionality
    updateShareButtonState();
});