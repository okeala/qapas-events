const R = 6371008.8;
export function constrainedGeometry(shape, dimensions) {
    const lat=Number(dimensions.lat), lng=Number(dimensions.lng), w=Number(dimensions.width||2.4);
    const h=shape==='square'?w:Number(dimensions.height||w), angle=Number(dimensions.angle||0)*Math.PI/180;
    let points;
    if(shape==='square'||shape==='rectangle') points=[[-w/2,-h/2],[w/2,-h/2],[w/2,h/2],[-w/2,h/2]];
    else {
        const n=shape==='circle'?64:Number(dimensions.sides||6), radius=Number(dimensions.radius||w/2);
        points=Array.from({length:n},(_,i)=>[Math.cos(2*Math.PI*i/n)*radius,Math.sin(2*Math.PI*i/n)*radius]);
    }
    const ring=points.map(([x,y])=>{
        const east=x*Math.cos(angle)-y*Math.sin(angle), north=x*Math.sin(angle)+y*Math.cos(angle);
        return [lng+east/R/Math.cos(lat*Math.PI/180)*180/Math.PI,lat+north/R*180/Math.PI];
    });
    ring.push(ring[0]); return {type:'Polygon',coordinates:[ring]};
}
