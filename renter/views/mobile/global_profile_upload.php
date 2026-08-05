<?php
// Ensure $user exists because the hidden form uses it
if (!isset($user)) {
    $user = []; // Fallback to avoid notices
}
?>
<!-- Include Cropper CSS/JS if not already included -->
<script>
    if (!window.Cropper) {
        document.write('<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css" rel="stylesheet">');
        document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"><\/script>');
    }
</script>

<!-- Cropper Modal Mobile -->
<div id="cropperModalMobile" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:999999; flex-direction:column; align-items:center; justify-content:center; padding: 20px; box-sizing: border-box; backdrop-filter: blur(10px);">
    <div style="background: #fff; width: 100%; max-width: 450px; border-radius: 24px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);">
        <div style="padding: 20px 24px; background: linear-gradient(135deg, var(--primary-purple), #8B5CF6); display: flex; justify-content: space-between; align-items: center;">
            <h4 style="margin: 0; color: #fff; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 2px;">
                <i class='bx bx-crop' style="font-size: 22px;"></i> Adjust Photo
            </h4>
            <button type="button" onclick="closeCropperMobile()" style="background: rgba(255,255,255,0.2); border: none; color: white; cursor: pointer; font-size: 24px; width: 36px; height: 36px; border-radius: 12px; display: flex; align-items: center; justify-content: center; transition: 0.2s;"><i class='bx bx-x'></i></button>
        </div>
        <div style="padding: 24px; background: #000; height: 350px; position: relative;">
            <img id="imageToCropMobile" style="max-width: 100%; max-height: 100%; display: block; margin: 0 auto;">
        </div>
        <div style="padding: 20px 24px; background: #fff; display: flex; gap: 12px; justify-content: flex-end;">
            <button type="button" onclick="closeCropperMobile()" style="padding: 12px 24px; background: #F1F5F9; border: none; border-radius: 14px; font-weight: 600; font-size: 13px; font-size: 15px; color: #64748B; cursor: pointer; flex: 1;">Cancel</button>
            <button type="button" onclick="applyCropMobile()" style="padding: 12px 24px; background: var(--primary-purple); border: none; border-radius: 14px; font-weight: 600; font-size: 13px; font-size: 15px; color: white; cursor: pointer; flex: 1; box-shadow: 0 8px 16px rgba(98, 75, 255, 0.2);">Crop & Save</button>
        </div>
    </div>
</div>

<form method="POST" action="profile.php" id="hiddenProfileFormMobile" class="hidden-form" style="display: none;">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>">
    <input type="hidden" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>">
    <input type="hidden" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
    <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
    <input type="hidden" name="whatsapp" value="<?php echo htmlspecialchars($user['whatsapp'] ?? ''); ?>">
    <input type="hidden" name="room_no" value="<?php echo htmlspecialchars($user['room_no'] ?? ''); ?>">
    <input type="hidden" name="about" value="<?php echo htmlspecialchars($user['about'] ?? ''); ?>">
    <input type="hidden" name="dob" value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>">
    <input type="hidden" name="gender" value="<?php echo htmlspecialchars($user['gender'] ?? ''); ?>">
    <input type="hidden" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
    <input type="hidden" name="emergency_contact_name" value="<?php echo htmlspecialchars($user['emergency_contact_name'] ?? ''); ?>">
    <input type="hidden" name="emergency_contact_relation" value="<?php echo htmlspecialchars($user['emergency_contact_relation'] ?? ''); ?>">
    <input type="hidden" name="emergency_contact_phone" value="<?php echo htmlspecialchars($user['emergency_contact_phone'] ?? ''); ?>">
    <input type="hidden" name="emergency_contact_address" value="<?php echo htmlspecialchars($user['emergency_contact_address'] ?? ''); ?>">
    <input type="file" id="profilePicInputMobile" accept="image/*">
    <input type="hidden" name="cropped_image" id="croppedImageInputMobile">
    <input type="hidden" name="save_profile" value="1">
</form>

<script>
    let cropperMobile = null;
    const profilePicInputMobile = document.getElementById('profilePicInputMobile');
    const cropperModalMobile = document.getElementById('cropperModalMobile');
    const imageToCropMobile = document.getElementById('imageToCropMobile');
    const croppedImageInputMobile = document.getElementById('croppedImageInputMobile');

    if (profilePicInputMobile) {
        profilePicInputMobile.onchange = function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    imageToCropMobile.src = event.target.result;
                    cropperModalMobile.style.display = 'flex';
                    if (cropperMobile) cropperMobile.destroy();
                    cropperMobile = new Cropper(imageToCropMobile, {
                        aspectRatio: 1,
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 1,
                        restore: false,
                        guides: false,
                        center: true,
                        highlight: false,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                        toggleDragModeOnDblclick: false,
                    });
                };
                reader.readAsDataURL(file);
            }
        };
    }

    function closeCropperMobile() {
        cropperModalMobile.style.display = 'none';
        if (profilePicInputMobile) profilePicInputMobile.value = '';
        if (cropperMobile) cropperMobile.destroy();
    }

    function applyCropMobile() {
        if (!cropperMobile) return;
        const canvas = cropperMobile.getCroppedCanvas({
            width: 400,
            height: 400,
            imageSmoothingQuality: 'high'
        });
        
        const base64Image = canvas.toDataURL('image/jpeg', 0.9);
        if (croppedImageInputMobile) croppedImageInputMobile.value = base64Image;
        
        // Update any previews immediately
        const headerPreviewMobile = document.getElementById('headerProfileImgMobile');
        if (headerPreviewMobile) {
            headerPreviewMobile.src = base64Image;
            headerPreviewMobile.style.display = 'block';
        }
        const headerFallback = document.getElementById('headerProfileFallbackMobile');
        if (headerFallback) headerFallback.style.display = 'none';

        // Additional fallback updates (e.g. on profile page itself)
        const profileAvatarMobile = document.getElementById('profileAvatarImgMobile');
        if (profileAvatarMobile) {
            profileAvatarMobile.src = base64Image;
            profileAvatarMobile.style.display = 'block';
        }
        const profileAvatarFallback = document.getElementById('profileAvatarFallbackMobile');
        if (profileAvatarFallback) profileAvatarFallback.style.display = 'none';

        cropperModalMobile.style.display = 'none';
        if (cropperMobile) cropperMobile.destroy();
        
        // Auto submit form to save picture
        const hiddenFormMobile = document.getElementById('hiddenProfileFormMobile');
        if (hiddenFormMobile) {
            hiddenFormMobile.submit();
        }
    }
</script>
