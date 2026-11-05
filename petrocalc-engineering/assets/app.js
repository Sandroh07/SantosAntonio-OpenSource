(function(){
  'use strict';
  const calc = window.PetroCalc;
  const $ = id => document.getElementById(id);
  const fieldIds = ['oilBbl','sg','gasActual','gasPressure','gasTemp','zActual','refPressure','refTemp','zStandard','prodOil','prodGas','boeFactor','density','depth','pipeD','pipeL','valueBbl','oilPrice'];
  const nf = (value,digits=2) => Number.isFinite(value) ? value.toLocaleString('en-US',{minimumFractionDigits:digits,maximumFractionDigits:digits}) : '—';
  const value=id => $(id).value;
  const set=(id,text)=>{ $(id).textContent=text; };
  let latest={};
  function evaluate(){
    const errors=[];
    latest={};
    ['kpiApi','kpiOil','kpiGas','kpiRevenue','outM3','outApi','outLitres','outSG','outGasStd','outGor','outGasM3','outBoe','outHydro','outPipe','outUsd'].forEach(id=>set(id,'—'));
    function section(callback){try{callback();}catch(e){errors.push(e.message);}}
    section(()=>{
      const b=calc.bblToM3(value('oilBbl'));
      const api=calc.apiFromSG(value('sg'));
      set('kpiApi',nf(api));set('kpiOil',nf(b));
      set('outM3',nf(b,3)+' m³');set('outApi',nf(api,3)+' °API');set('outLitres',nf(b*1000,2)+' L');set('outSG',nf(Number(value('sg')),4));
      latest['Crude API gravity']=api;latest['Oil volume m3']=b;
    });
    section(()=>{
      const result=calc.standardGasVolume({volumeActualM3:value('gasActual'),pressureActualBarA:value('gasPressure'),
        temperatureActualC:value('gasTemp'),zActual:value('zActual'),pressureStandardBarA:value('refPressure'),
        temperatureStandardC:value('refTemp'),zStandard:value('zStandard')});
      set('outGasStd',nf(result,3)+' m³');set('kpiGas',nf(result));latest['Gas at chosen reference m3']=result;
    });
    section(()=>{
      const gor=calc.gorScfPerStb(value('prodGas'),value('prodOil'));
      const gasM3=calc.scfToM3(value('prodGas'));
      const boe=calc.productionBoe(value('prodOil'),Number(value('prodGas'))/1000,value('boeFactor'));
      set('outGor',nf(gor,2)+' scf/STB');set('outGasM3',nf(gasM3,2)+' m³');set('outBoe',nf(boe,2)+' BOE');
      latest['Gas-oil ratio scf per STB']=gor;latest['Gas volume m3']=gasM3;latest['Energy equivalent BOE']=boe;
    });
    section(()=>{
      const pressure=calc.hydrostaticBar(value('density'),value('depth'));
      const volume=calc.pipeVolumeM3(value('pipeD'),value('pipeL'));
      set('outHydro',nf(pressure,3)+' bar');set('outPipe',nf(volume,3)+' m³');
      latest['Hydrostatic increment bar']=pressure;latest['Pipe capacity m3']=volume;
    });
    section(()=>{
      const usd=calc.oilGrossUsd(value('valueBbl'),value('oilPrice'));
      set('outUsd','$'+nf(usd,2));set('kpiRevenue',nf(usd,0));latest['Gross oil scenario USD']=usd;
    });
    $('errorBox').hidden = errors.length===0;
    $('errorBox').textContent=errors.length ? 'Check inputs: '+errors.join(' · ') : '';
  }
  // CSV with data sanitation against spreadsheet formula execution.
  const csvCell=(s)=>{
    s=String(s);
    if(/^[\s]*[=+@\-]/.test(s))s="'"+s;
    return '"'+s.replaceAll('"','""')+'"';
  };
  function downloadCSV(){
    evaluate();const timestamp=new Date().toISOString();
    const rows=[['PetroCalc Engineering','Measurement / reference conditions supplied by operator'],['Generated at',timestamp],['Output','Value'],...Object.entries(latest),[],['Parameter','Input'],...fieldIds.map(id=>[id,value(id)]),[],['Disclaimer','Illustrative engineering values; independent software; not custody transfer or certified fiscal measurement.'],['Support RedotPay','1612817131'],['Support Airtm','santosantonio.eng@gmail.com']];
    const csv='\uFEFF'+rows.map(row=>row.map(csvCell).join(',')).join('\r\n');
    const blob=new Blob([csv],{type:'text/csv;charset=utf-8;'});
    const url=URL.createObjectURL(blob);const a=document.createElement('a');a.href=url;a.download='petrocalc-scenario.csv';a.click();URL.revokeObjectURL(url);
  }
  function getSaved(){try{return JSON.parse(localStorage.getItem('petrocalc_scenarios_v1')||'[]');}catch{return [];}}
  function setSaved(s){try{localStorage.setItem('petrocalc_scenarios_v1',JSON.stringify(s));}catch(e){$('errorBox').hidden=false;$('errorBox').textContent='Local storage unavailable: '+e.message;}}
  function renderSaved(){
    $('savedScenarios').replaceChildren();
    for(const [index,item] of getSaved().entries()){
      const r=document.createElement('div');r.className='saved-row';
      const title=document.createElement('span');title.textContent=`${item.name} · ${new Date(item.at).toLocaleDateString()}`;r.append(title);
      const group=document.createElement('span');
      for(const [name,func] of [['Load',()=>{for(const id of fieldIds)if(item.inputs[id]!==undefined)$(id).value=item.inputs[id];evaluate();activate('oil');}],['Delete',()=>{const saved=getSaved();saved.splice(index,1);setSaved(saved);renderSaved();}]]){
        const btn=document.createElement('button');btn.type='button';btn.textContent=name;btn.addEventListener('click',func);group.append(btn);
      }
      r.append(group);$('savedScenarios').append(r);
    }
  }
  function activate(panel){
    document.querySelectorAll('.nav-item').forEach(b=>b.classList.toggle('active',b.dataset.panel===panel));
    document.querySelectorAll('.panel').forEach(p=>p.classList.toggle('active',p.id==='panel-'+panel));
  }
  document.querySelectorAll('.nav-item').forEach(b=>b.addEventListener('click',()=>activate(b.dataset.panel)));
  fieldIds.forEach(id=>$(id).addEventListener('input',evaluate));
  $('exportCsv').addEventListener('click',downloadCSV);
  $('saveScenario').addEventListener('click',()=>{const name=$('scenarioName').value.trim();if(!name){$('scenarioName').focus();return;}
    const entries=getSaved();entries.unshift({name,at:new Date().toISOString(),inputs:Object.fromEntries(fieldIds.map(id=>[id,value(id)]))});
    setSaved(entries.slice(0,50));$('scenarioName').value='';renderSaved();
  });
  $('clearScenarios').addEventListener('click',()=>{if(confirm('Delete all saved scenarios from this browser?')){setSaved([]);renderSaved();}});
  evaluate();renderSaved();
})();
