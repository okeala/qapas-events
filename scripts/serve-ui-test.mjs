import {mkdtempSync,writeFileSync,rmSync} from 'node:fs';
import {tmpdir} from 'node:os';
import {join} from 'node:path';
import {execFileSync,spawn} from 'node:child_process';

if(!process.env.UI_TEST_ADMIN_PASSWORD)throw new Error('UI test password must be provisioned by the test configuration.');
const directory=mkdtempSync(join(tmpdir(),'qapas-event-ui-'));
const database=join(directory,'test.sqlite');writeFileSync(database,'');
const env={...process.env,APP_ENV:'local',APP_DEBUG:'false',APP_URL:'http://127.0.0.1:8889',
    APP_KEY:'base64:buSUwc3CkSKq4W0TjykLUcNG5vQllMjtAldQbjAk8SA=',DB_CONNECTION:'sqlite',DB_DATABASE:database,DB_URL:'',
    APP_NAME:'QAPAS Événements',QAPAS_APP_NAME:'QAPAS Événements',NO_PROXY:'127.0.0.1,localhost,::1',no_proxy:'127.0.0.1,localhost,::1',
    QAPAS_EVENT_DEMO_ENABLED:'true',
    QAPAS_PUBLIC_URL:'http://127.0.0.1:8889',QAPAS_IDENTITY_ENABLED:'false',SESSION_COOKIE:'qapas_event_ui_test',SESSION_DRIVER:'file',CACHE_STORE:'file',MAIL_MAILER:'array'};
execFileSync('php',['artisan','migrate','--seed','--force'],{env,stdio:'ignore'});
execFileSync('php',['-r', 'require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); App\\Models\\Admin::create(["name"=>"UI test","email"=>"ui-admin@qapas.test","password"=>getenv("UI_TEST_ADMIN_PASSWORD"),"is_active"=>true]);'],{env,stdio:'ignore'});
const server=spawn('php',['artisan','serve','--no-reload','--host=127.0.0.1','--port=8889'],{env,stdio:'inherit'});
const cleanup=()=>{server.kill('SIGTERM');rmSync(directory,{recursive:true,force:true});};
process.on('SIGTERM',cleanup);process.on('SIGINT',cleanup);
server.on('exit',code=>{rmSync(directory,{recursive:true,force:true});process.exit(code??0);});
