import {defineConfig} from '@playwright/test';
import {randomBytes} from 'node:crypto';

process.env.UI_TEST_ADMIN_PASSWORD ??= randomBytes(24).toString('hex');
export default defineConfig({
    testDir:'./tests/Browser',fullyParallel:false,workers:1,timeout:60000,expect:{timeout:15000},
    reporter:'list',outputDir:'/tmp/qapas-event-ui-results',
    use:{baseURL:'http://127.0.0.1:8889',trace:'off',screenshot:'only-on-failure',
        launchOptions:process.env.UI_TEST_CHROMIUM_PATH?{executablePath:process.env.UI_TEST_CHROMIUM_PATH,args:process.env.UI_TEST_CHROMIUM_ARGS?JSON.parse(process.env.UI_TEST_CHROMIUM_ARGS):['--no-sandbox','--disable-dev-shm-usage']}:{}},
    projects:[{name:'desktop',use:{viewport:{width:1440,height:1000}}},{name:'mobile',use:{viewport:{width:390,height:844}}}],
    webServer:{command:'node scripts/serve-ui-test.mjs',url:'http://127.0.0.1:8889/up',reuseExistingServer:false,timeout:60000},
});
