import {test,expect} from '@playwright/test';

async function login(page){
    await page.goto('/admin/login');await page.locator('input[type=email]').fill('ui-admin@qapas.test');
    await page.locator('input[type=password]').fill(process.env.UI_TEST_ADMIN_PASSWORD);
    await livewireClick(page,page.locator('button[type=submit]'));await expect(page).toHaveURL(/\/admin\/?$/);
}

async function livewireClick(page,button){
    const [response]=await Promise.all([page.waitForResponse(r=>/\/livewire(?:-[^/]+)?\/update(?:\?|$)/.test(r.url())&&r.request().method()==='POST'),button.click()]);
    expect(response.status()).toBe(200);
}

test('public tour has an interactive map, live offers and matching poster without private data',async({page},info)=>{
    const errors=[];page.on('pageerror',error=>errors.push(error.message));
    await page.goto('/visites');await page.getByRole('link',{name:'Découvrir la visite'}).first().click();
    await expect(page.locator('.leaflet-container')).toBeVisible();await expect(page.locator('canvas.leaflet-zoom-animated')).toBeVisible();
    await expect(page.getByRole('heading',{name:'La visite guidée'})).toBeVisible();
    await page.screenshot({path:'/tmp/qapas-event-preview-'+info.project.name+'-tour.png'});
    const payload=await page.request.get(page.url()+'/plan.json');expect(payload.ok()).toBeTruthy();
    const data=await payload.json();expect(data.event.decision).toBe('pending');expect(data.offers.length).toBe(4);
    expect(JSON.stringify(data)).not.toMatch(/buyer_email|net_price_cents|person_reference|budget_lines/);
    const path=new URL(page.url()).pathname;await page.goto(path+'/poster');
    await expect(page.getByRole('img',{name:'QR code vers la visite et le statut courant'})).toBeVisible();
    expect(errors).toEqual([]);
});

test('all six private modules render and the plan editor saves a constrained item with four P',async({page},info)=>{
    const errors=[];page.on('pageerror',error=>errors.push(error.message));await login(page);
    for(const module of ['plan','costs','revenues','balance','communications','products']){
        await page.goto('/admin/event-'+module);await expect(page.getByText('Projet de démonstration :',{exact:false})).toBeVisible();
        await expect(page.locator('.planner-tabs')).toBeVisible();
    }
    await page.goto('/admin/event-plan');await expect(page.locator('.leaflet-container')).toBeVisible();
    await page.screenshot({path:'/tmp/qapas-event-preview-'+info.project.name+'-admin.png'});
    await livewireClick(page,page.getByRole('button',{name:'Structures',exact:true}));
    const name='Stand UI '+Date.now();await page.locator('#planner-plan-name').fill(name);
    await page.locator('#planner-plan-four_ps-product').fill('Une activité pour les familles');
    await page.locator('#planner-plan-four_ps-price').fill('Gratuité pour les visiteurs');
    await page.locator('#planner-plan-four_ps-place').fill('Le parcours du village');
    await page.locator('#planner-plan-four_ps-promotion').fill('Invitation sur l’affiche');
    await livewireClick(page,page.getByRole('button',{name:'Enregistrer',exact:true}));
    await expect(page.getByRole('button',{name,exact:true})).toBeVisible();
    await expect(page.getByText('Élément et dossier de coûts enregistrés.',{exact:true})).toBeVisible();
    expect(errors).toEqual([]);
});

test('PNG download is produced from the safe vector export',async({page})=>{
    await page.goto('/visites');await page.getByRole('link',{name:'Découvrir la visite'}).first().click();
    const download=page.waitForEvent('download');await page.getByRole('button',{name:'Plan PNG',exact:true}).click();
    const file=await download;expect(file.suggestedFilename()).toMatch(/-plan\.png$/);expect(await file.failure()).toBeNull();
});
