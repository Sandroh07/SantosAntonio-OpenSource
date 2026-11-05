const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const html=fs.readFileSync(path.join(__dirname,'..','index.html'),'utf8');
const elements=new Map();
for(const [,id,value=''] of html.matchAll(/<input\b[^>]*?\bid="([^"]+)"[^>]*>/g).map(match=>[match[0],match[1],(match[0].match(/\bvalue="([^"]*)"/)||[])[1]||''])){
 elements.set(id,{value,textContent:'',hidden:false,callbacks:{},addEventListener(event,callback){this.callbacks[event]=callback;}});
}
for(const m of html.matchAll(/\bid="([^"]+)"/g))if(!elements.has(m[1]))elements.set(m[1],{value:'',textContent:'',hidden:false,callbacks:{},addEventListener(event,callback){this.callbacks[event]=callback;},replaceChildren(){},append(){}});
const nav=[];const panels=[];
for(const m of html.matchAll(/class="nav-item[^"]*" data-panel="([^"]+)"/g))nav.push({dataset:{panel:m[1]},classList:{toggle(){}},addEventListener(){}});
for(const m of html.matchAll(/class="panel[^"]*" id="([^"]+)"/g))panels.push({id:m[1],classList:{toggle(){}}});
global.window={PetroCalc:require('../assets/calculations.js')};
global.document={getElementById:id=>elements.get(id),querySelectorAll:selector=>selector==='.nav-item'?nav:panels};
global.localStorage={getItem:()=>null,setItem:()=>{}};
require('../assets/app.js');
const output=id=>elements.get(id).textContent;
assert.match(output('outApi'),/34\.971/);
assert.match(output('outM3'),/1,987\.341/);
assert.match(output('outUsd'),/1,812,500\.00/);
elements.get('oilBbl').value='100';elements.get('oilBbl').callbacks.input();
assert.match(output('outM3'),/15\.899/);
elements.get('gasPressure').value='0';elements.get('gasPressure').callbacks.input();
assert.equal(output('outGasStd'),'—');
assert.equal(elements.get('errorBox').hidden,false);
console.log('PASS: browser application initial calculations, dynamic input updates, invalid-input handling');
