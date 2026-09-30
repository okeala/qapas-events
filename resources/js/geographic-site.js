import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
const maps = new Map();
function mount() {
 for (const [el,map] of maps) if (!el.isConnected) { map.remove(); maps.delete(el); }
 document.querySelectorAll('[data-geographic-plan]').forEach(el=>{
  if(maps.has(el))return;
  const data=JSON.parse(el.querySelector('[data-geo-json]').textContent), editor=el.closest('[data-geo-editor]');
  const map=L.map(el.querySelector('[data-geo-canvas]')).setView(data.center,data.zoom); maps.set(el,map);
  const osm=L.tileLayer(data.osm,{maxZoom:21,maxNativeZoom:19,attribution:'© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'}).addTo(map);
  const bases={'OpenStreetMap':osm};
  if(data.imagery.url && data.imagery.layers){
   const credit=document.createElement('span');credit.textContent=data.imagery.attribution;
   const photo=L.tileLayer.wms(data.imagery.url,{layers:data.imagery.layers,format:'image/png',transparent:false,version:'1.3.0',maxZoom:22,attribution:credit.outerHTML});
   let warned=false;photo.on('tileerror',()=>{if(!warned){warned=true;const status=editor?.querySelector('[data-geo-status]')||el.querySelector('[data-map-status]');if(status)status.textContent='Fond aérien indisponible : utilisez OpenStreetMap dans le sélecteur de fonds.';}});bases['Photo aérienne DGT 2025']=photo;
  }
  const groups={}, colors={activity:'#be4479',quartel:'#398657',stand:'#ce892a',tent:'#874cb2',path:'#286eaa',emergency:'#c0392b',exit:'#c0392b',entrance:'#23845d',parking:'#6e7580'};
  for(const f of data.geojson.features){
   const group=groups[f.properties.category]??=L.featureGroup().addTo(map);
   const label=document.createElement('span');label.textContent=f.properties.name;
   const popup=document.createElement('div');const title=document.createElement('strong');title.textContent=f.properties.name;popup.append(title);for(const activity of f.properties.activities||[]){const row=document.createElement('p'),link=document.createElement(activity.url?'a':'span');link.textContent=activity.name;if(activity.url)link.href=activity.url;row.append(link);popup.append(row);}
   L.geoJSON(f,{style:{color:colors[f.properties.category]||'#376e77',weight:3},pointToLayer:(_,latlng)=>L.circleMarker(latlng,{radius:8,color:colors[f.properties.category]||'#376e77'})}).bindTooltip(label).bindPopup(popup).addTo(group);
  }
  L.control.layers(bases,groups,{collapsed:false}).addTo(map);L.control.scale({imperial:false}).addTo(map);
  const all=L.featureGroup(Object.values(groups));if(all.getLayers().length)map.fitBounds(all.getBounds(),{padding:[25,25],maxZoom:19});
  if(editor){
   let drawing=false,points=[],draft=null,geometry=null;
   const field=s=>editor.querySelector(s), status=field('[data-geo-status]');
   const component=()=>{let root=editor;while(root&&!root.hasAttribute('wire:id'))root=root.parentElement;return window.Livewire.find(root.getAttribute('wire:id'));};
   const redraw=()=>{if(draft)map.removeLayer(draft);const type=field('[data-shape]').value;geometry=points.length?{type,coordinates:type==='Point'?points.at(-1):type==='Polygon'?[[...points,points[0]]]:points}:null;if(points.length)draft=L.geoJSON(geometry,{style:{color:'#dd7c20',dashArray:'6 5'},pointToLayer:(_,latlng)=>L.circleMarker(latlng,{radius:7,color:'#dd7c20'})}).addTo(map);};
   const isPlacement=()=>field('[data-feature]').value==='new-location'||field('[data-feature]').value.startsWith('location-');
   field('[data-feature]').onchange=()=>{
    const f=data.geojson.features.find(f=>f.id===field('[data-feature]').value), placement=isPlacement();
    field('[data-placement-fields]').hidden=!placement;field('[data-category]').disabled=placement;field('[data-shape]').disabled=placement;if(placement)field('[data-shape]').value='Polygon';
    field('[data-feature-name]').value=f?.properties.name||'';geometry=f?.geometry||null;drawing=false;if(draft)map.removeLayer(draft);
    field('[data-activity]').disabled=placement&&!!f;
    if(f){if(!placement)field('[data-category]').value=f.properties.category;else{field('[data-activity]').value=f.properties.activity;field('[data-quartel]').value=f.properties.quartel;field('[data-role]').value=f.properties.role;field('[data-access]').value=f.properties.access;field('[data-location-public]').checked=f.properties.is_public;for(const o of field('[data-additional]').options)o.selected=(f.properties.additional_quartels||[]).includes(o.value);}}
    status.textContent='Objet sélectionné. Tracer remplace le contour. Une emprise doit rester dans les quartéis sélectionnés.';
   };
   field('[data-start]').onclick=()=>{drawing=true;points=[];geometry=null;if(draft)map.removeLayer(draft);status.textContent='Cliquez sur le plan. Enregistrez lorsque le tracé est complet.';};
   field('[data-undo]').onclick=()=>{points.pop();redraw();};
   map.on('click',e=>{if(!drawing)return;if(points.length>=499)return;const point=[e.latlng.lng,e.latlng.lat];if(field('[data-shape]').value==='Point')points=[point];else points.push(point);redraw();});
   field('[data-save]').onclick=async()=>{if(!geometry||!field('[data-feature-name]').value.trim()){status.textContent='Indiquez un nom et dessinez l’objet.';return;}drawing=false;status.textContent='Enregistrement…';try{if(isPlacement())await component().call('savePlacement',field('[data-feature-name]').value.trim(),field('[data-activity]').value,field('[data-quartel]').value,Array.from(field('[data-additional]').selectedOptions,o=>o.value),geometry,field('[data-role]').value,field('[data-access]').value,field('[data-location-public]').checked,field('[data-feature]').value.startsWith('location-')?field('[data-feature]').value.slice(9):null);else await component().call('saveFeature',field('[data-feature-name]').value.trim(),field('[data-category]').value,geometry,field('[data-feature]').value||null);}catch{status.textContent='Objet non enregistré. Consultez les erreurs du formulaire.';}};
  }
  requestAnimationFrame(()=>map.invalidateSize());
 });
}
mount(); document.addEventListener('DOMContentLoaded',mount);document.addEventListener('livewire:navigated',mount);window.addEventListener('qapas-geo-mount',mount);
