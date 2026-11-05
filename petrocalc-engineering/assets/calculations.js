/* PetroCalc Engineering — auditable, dependency-free reference calculations.
 * Conversions refer to explicitly stated standard/reference conditions.
 * Not for custody transfer, custody pricing, safety-critical engineering or fiscal reporting.
 */
(function (root, factory) {
  const calculator = factory();
  if (typeof module === 'object' && module.exports) module.exports = calculator;
  if (root) root.PetroCalc = calculator;
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
  'use strict';
  const BBL_M3 = 0.158987294928;
  const SCF_M3 = 0.028316846592;
  const G = 9.80665;
  const PI = Math.PI;
  function finite(value, label) {
    const x = Number(value);
    if (!Number.isFinite(x) || value === '' || value == null) throw new RangeError(`${label} must be a finite number.`);
    return x;
  }
  function positive(value, label) {
    const x = finite(value, label);
    if (x <= 0) throw new RangeError(`${label} must be greater than zero.`);
    return x;
  }
  function nonNegative(value, label) {
    const x = finite(value, label);
    if (x < 0) throw new RangeError(`${label} cannot be negative.`);
    return x;
  }
  function apiFromSG(sg) {return 141.5 / positive(sg, 'Specific gravity at 60°F/60°F') - 131.5;}
  function sgFromAPI(api) {
    const a = finite(api, 'API gravity');
    if (a <= -131.5) throw new RangeError('API gravity must be greater than -131.5.');
    return 141.5 / (a + 131.5);
  }
  function bblToM3(bbl) {return nonNegative(bbl,'Barrels') * BBL_M3;}
  function m3ToBbl(m3) {return nonNegative(m3,'Cubic metres') / BBL_M3;}
  function scfToM3(scf) {return nonNegative(scf,'Standard cubic feet') * SCF_M3;}
  function m3ToScf(m3) {return nonNegative(m3,'Cubic metres') / SCF_M3;}
  /** Gas law: Vstd=Vact*(Pact/Pstd)*(Tstd/Tact)*(Zstd/Zact), P absolute, Kelvin T. */
  function standardGasVolume({volumeActualM3, pressureActualBarA, temperatureActualC,
                               zActual, pressureStandardBarA, temperatureStandardC, zStandard}) {
    const v=nonNegative(volumeActualM3,'Actual gas volume');
    const pa=positive(pressureActualBarA,'Actual absolute pressure');
    const ps=positive(pressureStandardBarA,'Reference absolute pressure');
    const ta=finite(temperatureActualC,'Actual temperature')+273.15;
    const ts=finite(temperatureStandardC,'Reference temperature')+273.15;
    const za=positive(zActual,'Actual Z factor');
    const zs=positive(zStandard,'Reference Z factor');
    if(ta<=0||ts<=0) throw new RangeError('Absolute temperature must be above 0 K.');
    return v*(pa/ps)*(ts/ta)*(zs/za);
  }
  function gorScfPerStb(gasScf, oilStb) {return nonNegative(gasScf,'Gas volume')/positive(oilStb,'Oil stock-tank volume');}
  function hydrostaticBar(densityKgM3, depthM) {return positive(densityKgM3,'Fluid density')*nonNegative(depthM,'Vertical depth')*G/100000;}
  function pipeVolumeM3(innerDiameterMm, lengthM) {
    const d=positive(innerDiameterMm,'Pipe internal diameter')/1000;
    return PI*(d/2)**2*nonNegative(lengthM,'Pipe length');
  }
  function oilGrossUsd(barrels, usdPerBbl) {return nonNegative(barrels,'Oil barrels')*nonNegative(usdPerBbl,'USD per barrel');}
  function productionBoe(oilBbl, gasMcf, mcfPerBoe=6) {
    return nonNegative(oilBbl,'Oil barrels') + nonNegative(gasMcf,'Gas Mcf')/positive(mcfPerBoe,'Mcf per BOE assumption');
  }
  return Object.freeze({BBL_M3,SCF_M3,G,apiFromSG,sgFromAPI,bblToM3,m3ToBbl,scfToM3,m3ToScf,
    standardGasVolume,gorScfPerStb,hydrostaticBar,pipeVolumeM3,oilGrossUsd,productionBoe});
});
