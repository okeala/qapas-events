import test from 'node:test';
import assert from 'node:assert/strict';
import {constrainedGeometry} from '../../resources/js/plan-geometry.js';
const R=6371008.8;
function metres(p,origin){return [(p[0]-origin[0])*Math.PI/180*R*Math.cos(origin[1]*Math.PI/180),(p[1]-origin[1])*Math.PI/180*R];}
test('a rotated square retains four equal 2.4 metre sides at the site latitude',()=>{
    const geometry=constrainedGeometry('square',{lat:40.33,lng:-7.35,width:2.4,height:99,angle:37});
    const ring=geometry.coordinates[0].map(p=>metres(p,[-7.35,40.33]));
    for(let i=1;i<ring.length;i++)assert.ok(Math.abs(Math.hypot(ring[i][0]-ring[i-1][0],ring[i][1]-ring[i-1][1])-2.4)<1e-7);
    assert.deepEqual(geometry.coordinates[0][0],geometry.coordinates[0].at(-1));
});
test('a regular polygon preserves its selected radius and number of vertices',()=>{
    const ring=constrainedGeometry('regular_polygon',{lat:50,lng:4,sides:7,radius:3,angle:20}).coordinates[0];
    assert.equal(ring.length,8);
    for(const p of ring)assert.ok(Math.abs(Math.hypot(...metres(p,[4,50]))-3)<1e-7);
});
test('circle approximation and rectangle dimensions remain separate',()=>{
    assert.equal(constrainedGeometry('circle',{lat:0,lng:0,radius:5}).coordinates[0].length,65);
    const ring=constrainedGeometry('rectangle',{lat:0,lng:0,width:3,height:8}).coordinates[0].map(p=>metres(p,[0,0]));
    assert.ok(Math.abs(ring[1][0]-ring[0][0]-3)<1e-7); assert.ok(Math.abs(ring[2][1]-ring[1][1]-8)<1e-7);
});
