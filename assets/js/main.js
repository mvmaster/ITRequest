/**
 * Main JavaScript file for IT Request System
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Custom file input label
    document.querySelectorAll('.custom-file-input').forEach(function(input) {
        input.addEventListener('change', function(e) {
            var fileName = '';
            if (this.files && this.files.length > 1) {
                fileName = (this.getAttribute('data-multiple-caption') || '').replace('{count}', this.files.length);
            } else {
                fileName = e.target.value.split('\\').pop();
            }
            
            if (fileName) {
                let label = this.nextElementSibling;
                label.innerHTML = fileName;
            }
        });
    });
    
    // Request type selection in create-request form
    const requestTypeSelect = document.getElementById('request_type');
    if (requestTypeSelect) {
        requestTypeSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const description = selectedOption.getAttribute('data-description');
            const descriptionElement = document.getElementById('type-description');
            
            if (descriptionElement) {
                if (description) {
                    descriptionElement.textContent = description;
                    descriptionElement.parentElement.style.display = 'block';
                } else {
                    descriptionElement.parentElement.style.display = 'none';
                }
            }
        });
    }
    
    // Dynamic attachment fields in create-request form
    const addAttachmentBtn = document.getElementById('add-attachment');
    if (addAttachmentBtn) {
        addAttachmentBtn.addEventListener('click', function() {
            const attachmentsContainer = document.getElementById('attachments-container');
            const attachmentCount = attachmentsContainer.querySelectorAll('.attachment-input').length;
            
            if (attachmentCount < 5) { // Limit to 5 attachments
                const newAttachment = document.createElement('div');
                newAttachment.className = 'mb-3 attachment-input';
                newAttachment.innerHTML = `
                    <div class="input-group">
                        <input type="file" name="attachment[]" class="form-control" id="attachment-${attachmentCount + 1}">
                        <button type="button" class="btn btn-outline-danger remove-attachment">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                
                attachmentsContainer.appendChild(newAttachment);
                
                // Add event listener to remove button
                newAttachment.querySelector('.remove-attachment').addEventListener('click', function() {
                    attachmentsContainer.removeChild(newAttachment);
                    if (attachmentsContainer.querySelectorAll('.attachment-input').length < 5) {
                        addAttachmentBtn.disabled = false;
                    }
                });
                
                // Disable add button if reached limit
                if (attachmentsContainer.querySelectorAll('.attachment-input').length >= 5) {
                    addAttachmentBtn.disabled = true;
                }
            }
        });
    }
    
    // Handle removal of existing attachments
    document.querySelectorAll('.remove-attachment').forEach(function(button) {
        button.addEventListener('click', function() {
            const attachmentItem = this.closest('.attachment-input');
            if (attachmentItem) {
                attachmentItem.parentElement.removeChild(attachmentItem);
                const addAttachmentBtn = document.getElementById('add-attachment');
                if (addAttachmentBtn) {
                    addAttachmentBtn.disabled = false;
                }
            }
        });
    });
    
    // AJAX request for tracking
    const trackForm = document.getElementById('track-form');
    if (trackForm) {
        trackForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const refNo = document.getElementById('reference_no').value;
            const resultDiv = document.getElementById('tracking-result');
            
            if (!refNo) {
                resultDiv.innerHTML = '<div class="alert alert-danger">กรุณากรอกหมายเลขอ้างอิง</div>';
                return;
            }
            
            resultDiv.innerHTML = '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            
            // AJAX request to track-api.php
            fetch('track-api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'reference_no=' + encodeURIComponent(refNo)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Render request information
                    resultDiv.innerHTML = `
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">ข้อมูลคำขอ ${data.request.reference_no}</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p><strong>หัวข้อ:</strong> ${data.request.subject}</p>
                                        <p><strong>ประเภท:</strong> ${data.request.type_name}</p>
                                        <p><strong>สถานะ:</strong> ${data.request.status_badge}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>วันที่สร้าง:</strong> ${data.request.created_at}</p>
                                        <p><strong>อัพเดตล่าสุด:</strong> ${data.request.updated_at}</p>
                                        <p><strong>ความสำคัญ:</strong> ${data.request.priority_badge}</p>
                                    </div>
                                </div>
                                
                                <h6 class="border-bottom pb-2 mb-3">ประวัติการดำเนินการ</h6>
                                <ul class="timeline">
                                    ${data.logs.map(log => `
                                        <li>
                                            <div class="timeline-badge ${log.status}">
                                                <i class="fas fa-check"></i>
                                            </div>
                                            <div class="timeline-panel">
                                                <div class="d-flex justify-content-between">
                                                    <h6 class="mb-1">${log.status_thai}</h6>
                                                    <small>${log.created_at}</small>
                                                </div>
                                                <p class="mb-0">${log.comment}</p>
                                                <small class="text-muted">โดย: ${log.performed_by_name}</small>
                                            </div>
                                        </li>
                                    `).join('')}
                                </ul>
                            </div>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                resultDiv.innerHTML = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการค้นหาข้อมูล กรุณาลองใหม่อีกครั้ง</div>';
            });
        });
    }
    
    // Filter for admin request list
    const requestFilter = document.getElementById('request-filter');
    if (requestFilter) {
        requestFilter.addEventListener('change', function() {
            const status = this.value;
            window.location.href = 'manage-requests.php' + (status ? '?status=' + status : '');
        });
    }
    
    // Status update for admin
    const updateStatusForm = document.getElementById('update-status-form');
    if (updateStatusForm) {
        updateStatusForm.addEventListener('submit', function(e) {
            if (!document.getElementById('comment').value.trim()) {
                e.preventDefault();
                alert('กรุณากรอกความคิดเห็น');
                return false;
            }
        });
    }
});
