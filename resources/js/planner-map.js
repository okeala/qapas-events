import L from 'leaflet';
import '@geoman-io/leaflet-geoman-free';
import 'leaflet/dist/leaflet.css';
import '@geoman-io/leaflet-geoman-free/dist/leaflet-geoman.css';
import { constrainedGeometry } from './plan-geometry.js';

const instances=new Map();
function textNode(value,tag='span'){const node=document.createElement(tag);node.textContent=value||'';return node;}
function componentFor(root){const host=root.closest('[wire\\:id]');return host&&window.Livewire?.find(host.getAttribute('wire:id'));}
async function loadData(root){
    const response=await fetch(root.dataset.api,{headers:{Accept:'application/json'},credentials:'same-origin'});
    if(!response.ok) throw new Error('Le plan est indisponible. Rechargez la page.');
    return response.json();
}
async function initialize(root){
    if(instances.has(root))return;
    const container=root.querySelector('.planner-map'); const admin=root.dataset.admin==='true';
    const center=JSON.parse(root.dataset.center||'[0,0]');
    const map=L.map(container,{preferCanvas:true}).setView(center,19);
    const tiles=root.dataset.tiles||'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
    if(tiles.startsWith('https://'))L.tileLayer(tiles,{maxZoom:22,maxNativeZoom:19,attribution:'© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'}).addTo(map);
    const groups=L.featureGroup().addTo(map); const controller=new AbortController();
    const state={map,groups,controller,features:[],selected:null,preview:null}; instances.set(root,state);
    if(admin){
        map.pm.addControls({position:'topleft',drawMarker:false,drawCircleMarker:false,drawRectangle:false,drawCircle:false,drawPolygon:true,drawPolyline:true,drawText:false,editMode:false,dragMode:false,removalMode:false,rotateMode:false,cutPolygon:false});
        map.pm.setLang('fr');
        map.on('pm:create',event=>{
            const wire=componentFor(root); if(!wire)return;
            const plan=wire.get('plan'),line=event.shape==='Line';
            if((!line&&!['areas','routes_accesses'].includes(plan.category))||(line&&!['routes_accesses','barriers','electricity','audiovisual'].includes(plan.category))){
                map.removeLayer(event.layer);container.after(textNode('Choisissez une catégorie Espaces ou Routes pour un tracé libre, ou un gabarit pour un équipement.','p'));return;
            }
            wire.set('plan.geometry',event.layer.toGeoJSON().geometry,false);
            wire.set('plan.shape',event.shape==='Line'?'line':'free_polygon',true);
            if(state.preview)map.removeLayer(state.preview);state.preview=event.layer;
        });
        map.on('click',event=>{
            if(map.pm.globalDrawModeEnabled())return;
            const wire=componentFor(root),plan=wire?.get('plan'); if(!wire||!plan||['free_polygon','line'].includes(plan.shape))return;
            wire.set('plan.dimensions.lat',event.latlng.lat,false);wire.set('plan.dimensions.lng',event.latlng.lng,false);
            const geometry=constrainedGeometry(plan.shape,{...plan.dimensions,lat:event.latlng.lat,lng:event.latlng.lng});
            if(state.preview)map.removeLayer(state.preview);
            state.preview=L.geoJSON(geometry,{style:{color:plan.color||'#258052',dashArray:'4 4',fillOpacity:.3}}).addTo(map);
        });
    }
    async function refresh(){
        const data=await loadData(root); const collection=data.plan||data; state.features=collection.features||[];
        groups.clearLayers();if(state.preview){map.removeLayer(state.preview);state.preview=null;}
        for(const feature of state.features){
            const properties=feature.properties;
            const layer=L.geoJSON(feature,{style:{color:properties.color,weight:2,fillOpacity:.35},onEachFeature:(f,l)=>{
                l.bindTooltip(textNode(properties.name),{permanent:false,direction:'center'});
                if(admin){
                    l.on('click',event=>{
                        L.DomEvent.stopPropagation(event); const wire=componentFor(root); wire?.call('selectElement',properties.id);
                        if(state.selected){state.selected.pm?.disable();state.selected.pm?.disableLayerDrag();}
                        state.selected=l;
                        if(['free_polygon','line'].includes(properties.shape))l.pm?.enable({allowSelfIntersection:false});
                        else l.pm?.enableLayerDrag();
                    });
                    l.on('pm:edit',()=>componentFor(root)?.set('plan.geometry',l.toGeoJSON().geometry,false));
                    l.on('pm:dragend',()=>{
                        const geometry=l.toGeoJSON().geometry,wire=componentFor(root);
                        if(['free_polygon','line'].includes(properties.shape))wire?.set('plan.geometry',geometry,false);
                        else{const ring=geometry.coordinates[0].slice(0,-1);wire?.set('plan.dimensions.lat',ring.reduce((a,p)=>a+p[1],0)/ring.length,false);wire?.set('plan.dimensions.lng',ring.reduce((a,p)=>a+p[0],0)/ring.length,false);}
                    });
                }else{
                    const content=textNode('','div');content.append(textNode(properties.name,'strong'),textNode(properties.description,'p'));
                    const link=document.createElement('a');link.href='#stop-'+properties.uuid;link.textContent='Découvrir cette étape';content.append(link);l.bindPopup(content);
                }
            }});
            layer.addTo(groups);layer.feature=feature;
        }
        if(groups.getBounds().isValid()&&!state.loaded){map.fitBounds(groups.getBounds().pad(.12),{maxZoom:21});state.loaded=true;}
        map.invalidateSize();
    }
    try{await refresh();}catch(error){container.after(textNode(error.message,'p'));}
    window.addEventListener('planner-updated',()=>refresh().catch(()=>{}),{signal:controller.signal});
    document.addEventListener('click',event=>{
        const button=event.target.closest('[data-focus-element]');if(!button)return;
        const uuid=button.dataset.focusElement;
        groups.eachLayer(layer=>{if(layer.feature?.properties.uuid===uuid){map.fitBounds(layer.getBounds().pad(.7),{maxZoom:21});layer.eachLayer?.(part=>part.openPopup());}});
    },{signal:controller.signal});
    const search=document.querySelector('[data-plan-search]'),filter=document.querySelector('[data-plan-category]');
    function applyFilter(){groups.eachLayer(layer=>{const p=layer.feature?.properties;if(!p)return;const match=(!filter?.value||p.category===filter.value)&&(!search?.value||`${p.name} ${p.description}`.toLocaleLowerCase().includes(search.value.toLocaleLowerCase()));if(match&&!map.hasLayer(layer))map.addLayer(layer);else if(!match&&map.hasLayer(layer))map.removeLayer(layer);});}
    search?.addEventListener('input',applyFilter,{signal:controller.signal});filter?.addEventListener('change',applyFilter,{signal:controller.signal});
}
function initializeAll(){
    for(const [root,state]of instances)if(!document.contains(root)){state.controller.abort();state.map.remove();instances.delete(root);}
    document.querySelectorAll('[data-planner-map]').forEach(root=>initialize(root));
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initializeAll);else initializeAll();
document.addEventListener('livewire:navigated',initializeAll);
let hookRegistered=false;
function registerLivewire(){if(window.Livewire&&!hookRegistered){hookRegistered=true;window.Livewire.hook('morph.updated',()=>queueMicrotask(initializeAll));}}
registerLivewire();document.addEventListener('livewire:init',registerLivewire);
document.addEventListener('click',async event=>{
    const button=event.target.closest('[data-export-png]');if(!button)return;
    button.disabled=true;
    let url;
    try{
        const response=await fetch(button.dataset.exportPng,{credentials:'same-origin'});if(!response.ok)throw new Error('Export indisponible.');
        url=URL.createObjectURL(await response.blob());const img=new Image();img.src=url;await img.decode();
        const canvas=document.createElement('canvas');canvas.width=img.naturalWidth*2;canvas.height=img.naturalHeight*2;
        canvas.getContext('2d').drawImage(img,0,0,canvas.width,canvas.height);
        const blob=await new Promise(resolve=>canvas.toBlob(resolve,'image/png'));if(!blob)throw new Error('Export indisponible.');
        const download=URL.createObjectURL(blob),link=document.createElement('a');link.href=download;link.download=button.dataset.filename;link.click();setTimeout(()=>URL.revokeObjectURL(download),1000);
    }catch(error){button.after(textNode(error.message,'p'));}
    finally{if(url)URL.revokeObjectURL(url);button.disabled=false;}
});
