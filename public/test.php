<?php
echo '<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>رفع وضغط فيديو</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background-color: #000; color: #fff; font-family: monospace; padding: 20px; }
  .video-container { margin-top: 30px; }
  video { max-width: 100%; border: 1px solid #fff; }
  .video-info { margin-top: 5px; font-size: 0.9rem; }
</style>
</head>
<body>';

$ffmpegPath = '/home/azsystems-motors/htdocs/motors.azsystems.tech/public/ffmpeg-7.0.2-amd64-static/ffmpeg';
$uploadDir = 'removablemedia';

// إنشاء مجلد لو مش موجود
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$originalVideo = '';
$compressedVideo = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['video'])) {
    $file = $_FILES['video'];

    // تحقق من رفع الفيديو بدون أخطاء
    if ($file['error'] === UPLOAD_ERR_OK) {
        $tmpName = $file['tmp_name'];
        $originalName = basename($file['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // تحقق نوع الملف (mp4 فقط)
        if ($ext === 'mp4') {
            $originalVideo = $uploadDir . '/' . $originalName;
            if (move_uploaded_file($tmpName, $originalVideo)) {
                // اسم الملف المضغوط
                $compressedVideo = $uploadDir . '/compressed_' . $originalName;

                // أمر ضغط الفيديو (يمكنك تعديل الإعدادات حسب الحاجة)
                // مثال: تخفيض الجودة والدقة
                $cmd = escapeshellcmd($ffmpegPath) . " -i " . escapeshellarg($originalVideo) .
                    " -vcodec libx264 -crf 28 -preset fast " . escapeshellarg($compressedVideo) . " -y 2>&1";

                exec($cmd, $output, $returnVar);

                if ($returnVar !== 0) {
                    $errors[] = "فشل ضغط الفيديو:<br>" . implode("<br>", $output);
                    $compressedVideo = '';
                }
            } else {
                $errors[] = "فشل رفع الملف.";
            }
        } else {
            $errors[] = "الرجاء رفع ملف فيديو بصيغة MP4 فقط.";
        }
    } else {
        $errors[] = "حدث خطأ أثناء رفع الملف.";
    }
}

function formatSizeUnits($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' bytes';
}

?>

<div class="container">
  <h1 class="mb-4">رفع وضغط فيديو</h1>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <?php echo implode('<br>', $errors); ?>
    </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="mb-5">
    <div class="mb-3">
      <label for="video" class="form-label">اختر فيديو بصيغة MP4</label>
      <input class="form-control" type="file" id="video" name="video" accept="video/mp4" required>
    </div>
    <button type="submit" class="btn btn-primary">رفع وضغط الفيديو</button>
  </form>

  <?php if ($originalVideo && file_exists($originalVideo)): ?>
    <div class="row video-container">
      <div class="col-md-6 text-center mb-4">
        <h5>الفيديو الأصلي</h5>
        <video controls src="<?php echo $originalVideo; ?>"></video>
        <div class="video-info">
          الاسم: <?php echo basename($originalVideo); ?><br>
          الحجم: <?php echo formatSizeUnits(filesize($originalVideo)); ?>
        </div>
      </div>

      <?php if ($compressedVideo && file_exists($compressedVideo)): ?>
      <div class="col-md-6 text-center mb-4">
        <h5>الفيديو المضغوط</h5>
        <video controls src="<?php echo $compressedVideo; ?>"></video>
        <div class="video-info">
          الاسم: <?php echo basename($compressedVideo); ?><br>
          الحجم: <?php echo formatSizeUnits(filesize($compressedVideo)); ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

</body>
</html>
