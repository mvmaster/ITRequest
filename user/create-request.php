<?php
// Include necessary files
require_once '../config/database.php';
require_once '../config/app.php';
require_once '../auth/session.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: ../auth/login.php");
    exit;
}

// Get request types
$requestTypes = getRequestTypes();

// Handle form submission
$errors = [];
$success = false;
$requestCreated = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Connect to database
    $conn = connectDB();
    
    // Validate input
    if (empty($_POST['type_id'])) {
        $errors[] = "กรุณาเลือกประเภทคำขอ";
    }
    
    if (empty($_POST['subject'])) {
        $errors[] = "กรุณากรอกหัวข้อเรื่อง";
    }
    
    if (empty($_POST['description'])) {
        $errors[] = "กรุณากรอกรายละเอียด";
    }
    
    // If no errors, process the request
    if (empty($errors)) {
        // Sanitize input
        $typeId = (int)$_POST['type_id'];
        $subject = sanitizeInput($_POST['subject'], $conn);
        $description = sanitizeInput($_POST['description'], $conn);
        $contactInfo = isset($_POST['contact_info']) ? sanitizeInput($_POST['contact_info'], $conn) : '';
        $priority = isset($_POST['priority']) ? sanitizeInput($_POST['priority'], $conn) : 'medium';
        $userId = getCurrentUserId();
        
        // Generate reference number
        $referenceNo = generateReferenceNumber();
        
        // Create request
        $sql = "INSERT INTO requests (reference_no, user_id, type_id, subject, description, contact_info, priority, status) 
                VALUES ('$referenceNo', $userId, $typeId, '$subject', '$description', '$contactInfo', '$priority', 'pending')";
        
        if ($conn->query($sql)) {
            $requestId = $conn->insert_id;
            
            // Add initial log
            addRequestLog($requestId, 'pending', 'คำขอถูกสร้างขึ้น', $userId);
            
            // Handle file uploads
            $attachments = [];
            if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
                $fileCount = count($_FILES['attachments']['name']);
                
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES['attachments']['error'][$i] === 0) { // No error
                        $fileUpload = [
                            'name' => $_FILES['attachments']['name'][$i],
                            'type' => $_FILES['attachments']['type'][$i],
                            'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
                            'error' => $_FILES['attachments']['error'][$i],
                            'size' => $_FILES['attachments']['size'][$i]
                        ];
                        
                        $uploadResult = uploadFile($fileUpload, '../uploads/');
                        
                        if ($uploadResult['status']) {
                            // Save attachment info to database
                            $fileName = sanitizeInput($uploadResult['file_name'], $conn);
                            $filePath = sanitizeInput($uploadResult['file_path'], $conn);
                            $fileType = sanitizeInput($uploadResult['file_type'], $conn);
                            $fileSize = (int)$uploadResult['file_size'];
                            
                            $attachSql = "INSERT INTO attachments (request_id, file_name, file_path, file_type, file_size) 
                                          VALUES ($requestId, '$fileName', '$filePath', '$fileType', $fileSize)";
                            
                            if ($conn->query($attachSql)) {
                                $attachments[] = [
                                    'attachment_id' => $conn->insert_id,
                                    'file_name' => $fileName
                                ];
                            }
                        }
                    }
                }
            }
            
            $success = true;
            $requestCreated = [
                'request_id' => $requestId,
                'reference_no' => $referenceNo,
                'attachments' => $attachments
            ];
        } else {
            $errors[] = "เกิดข้อผิดพลาดในการสร้างคำขอ: " . $conn->error;
        }
    }
    
    closeDB($conn);
}

?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2 class="page-header">
                <i class="fas fa-plus-circle"></i> สร้าง IT Request ใหม่
            </h2>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <?php if ($success) : ?>
                <div class="alert alert-success">
                    <h4 class="alert-heading"><i class="fas fa-check-circle"></i> สร้างคำขอสำเร็จ!</h4>
                    <p>คำขอของคุณได้ถูกสร้างเรียบร้อยแล้ว คุณสามารถติดตามสถานะได้โดยใช้หมายเลขอ้างอิง</p>
                    <hr>
                    <p class="mb-0">
                        <strong>หมายเลขอ้างอิง:</strong> <?php echo $requestCreated['reference_no']; ?><br>
                        <strong>รหัสคำขอ:</strong> <?php echo $requestCreated['request_id']; ?><br>
                        <?php if (count($requestCreated['attachments']) > 0) : ?>
                            <strong>ไฟล์แนบ:</strong> <?php echo count($requestCreated['attachments']); ?> ไฟล์
                        <?php endif; ?>
                    </p>
                    <div class="mt-3">
                        <a href="view-request.php?id=<?php echo $requestCreated['request_id']; ?>" class="btn btn-primary">
                            <i class="fas fa-eye"></i> ดูรายละเอียดคำขอ
                        </a>
                        <a href="create-request.php" class="btn btn-outline-primary">
                            <i class="fas fa-plus"></i> สร้างคำขอใหม่
                        </a>
                    </div>
                </div>
            <?php else : ?>
                <?php if (!empty($errors)) : ?>
                    <div class="alert alert-danger">
                        <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> พบข้อผิดพลาด</h4>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error) : ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">กรอกข้อมูลคำขอ</h5>
                    </div>
                    <div class="card-body">
                        <form action="create-request.php" method="post" enctype="multipart/form-data">
                            <!-- ประเภทคำขอ -->
                            <div class="mb-3">
                                <label for="request_type" class="form-label required-field">ประเภทคำขอ</label>
                                <select name="type_id" id="request_type" class="form-select" required>
                                    <option value="">-- เลือกประเภทคำขอ --</option>
                                    <?php foreach ($requestTypes as $type) : ?>
                                        <option value="<?php echo $type['type_id']; ?>" data-description="<?php echo htmlspecialchars($type['description']); ?>">
                                            <?php echo htmlspecialchars($type['type_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- คำอธิบายประเภทคำขอ -->
                            <div class="mb-3 d-none" id="type-description-container">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <span id="type-description"></span>
                                </div>
                            </div>
                            
                            <!-- หัวข้อเรื่อง -->
                            <div class="mb-3">
                                <label for="subject" class="form-label required-field">หัวข้อเรื่อง</label>
                                <input type="text" name="subject" id="subject" class="form-control" required
                                       placeholder="ระบุหัวข้อเรื่องที่ต้องการแจ้ง" maxlength="255">
                            </div>
                            
                            <!-- ระดับความสำคัญ -->
                            <div class="mb-3">
                                <label for="priority" class="form-label">ระดับความสำคัญ</label>
                                <select name="priority" id="priority" class="form-select">
                                    <option value="low">ต่ำ</option>
                                    <option value="medium" selected>ปานกลาง</option>
                                    <option value="high">สูง</option>
                                    <option value="urgent">เร่งด่วน</option>
                                </select>
                            </div>
                            
                            <!-- รายละเอียด -->
                            <div class="mb-3">
                                <label for="description" class="form-label required-field">รายละเอียด</label>
                                <textarea name="description" id="description" class="form-control" rows="5" required
                                          placeholder="อธิบายรายละเอียดของเรื่องที่ต้องการแจ้ง"></textarea>
                            </div>
                            
                            <!-- ช่องทางการติดต่อกลับ -->
                            <div class="mb-3">
                                <label for="contact_info" class="form-label">ช่องทางการติดต่อกลับ</label>
                                <textarea name="contact_info" id="contact_info" class="form-control" rows="2"
                                          placeholder="ระบุช่องทางการติดต่อกลับ เช่น เบอร์โต๊ะทำงาน เบอร์มือถือ หรือ Line ID"></textarea>
                            </div>
                            
                            <!-- ไฟล์แนบ -->
                            <div class="mb-3">
                                <label class="form-label">ไฟล์แนบ (สูงสุด 5 ไฟล์, ขนาดไม่เกิน 5MB ต่อไฟล์)</label>
                                <div id="attachments-container">
                                    <div class="mb-3 attachment-input">
                                        <input type="file" name="attachments[]" class="form-control" id="attachment-1">
                                    </div>
                                </div>
                                <button type="button" id="add-attachment" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-plus"></i> เพิ่มไฟล์แนบ
                                </button>
                                <small class="form-text text-muted">
                                    รองรับไฟล์: JPG, JPEG, PNG, GIF, PDF, DOC, DOCX, XLS, XLSX, TXT, ZIP, RAR
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> ส่งคำขอ
                                </button>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> ยกเลิก
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">คำแนะนำในการแจ้งปัญหา</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success"></i> เลือกประเภทคำขอให้ตรงกับลักษณะปัญหา
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success"></i> ตั้งชื่อหัวข้อที่สื่อความหมายชัดเจน
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success"></i> อธิบายรายละเอียดปัญหาอย่างครบถ้วน
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success"></i> แนบภาพถ่ายหรือเอกสารที่เกี่ยวข้อง (ถ้ามี)
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success"></i> ระบุช่องทางการติดต่อกลับที่สะดวก
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">ตัวอย่างการเขียนรายละเอียดที่ดี</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <strong>ตัวอย่างที่ดี:</strong>
                        <p class="mb-1 mt-2">
                            "เครื่องคอมพิวเตอร์ในแผนกบัญชี รหัสเครื่อง ACC-PC-023 ไม่สามารถเปิดเครื่องได้ 
                            หน้าจอแสดงข้อความ 'No boot device found' ทุกครั้งที่พยายามเปิดเครื่อง 
                            เริ่มพบปัญหาตั้งแต่เช้าวันนี้ (9 เม.ย. 2025) เวลาประมาณ 09:00 น. ได้ลองถอดปลั๊กและเสียบใหม่แล้ว 
                            แต่ยังไม่สามารถแก้ไขได้"
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // แสดงคำอธิบายประเภทคำขอเมื่อเลือก
    document.addEventListener('DOMContentLoaded', function() {
        const requestTypeSelect = document.getElementById('request_type');
        if (requestTypeSelect) {
            requestTypeSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const description = selectedOption.getAttribute('data-description');
                const descriptionElement = document.getElementById('type-description');
                const descriptionContainer = document.getElementById('type-description-container');
                
                if (description && descriptionElement && descriptionContainer) {
                    descriptionElement.textContent = description;
                    descriptionContainer.classList.remove('d-none');
                } else if (descriptionContainer) {
                    descriptionContainer.classList.add('d-none');
                }
            });
        }
        
        // Dynamic attachment fields
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
                            <input type="file" name="attachments[]" class="form-control" id="attachment-${attachmentCount + 1}">
                            <button type="button" class="btn btn-outline-danger remove-attachment">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                    
                    attachmentsContainer.appendChild(newAttachment);
                    
                    // Add event listener to remove button
                    newAttachment.querySelector('.remove-attachment').addEventListener('click', function() {
                        attachmentsContainer.removeChild(newAttachment);
                        
                        // Enable add button if we're under the limit
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
    });
</script>

<?php include '../includes/footer.php'; ?>