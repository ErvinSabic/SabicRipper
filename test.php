<?php $image = 'https://static.wixstatic.com/media/6f6e33_4e2920af05b4440f87880154b5cfcc80~mv2_d_1500_1500_s_2.png'; $imageData = base64_encode(file_get_contents($image)); $src = 'data: image/png;base64,'.$imageData; echo '<img src="' . $src . '">'; ?>