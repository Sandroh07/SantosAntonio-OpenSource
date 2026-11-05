<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/bootstrap.php';
function q_all(PDO $db,string $sql,array $params=[]): array {$s=$db->prepare($sql);$s->execute($params);return $s->fetchAll();}
function q_one(PDO $db,string $sql,array $params=[]): array|false {$s=$db->prepare($sql);$s->execute($params);return $s->fetch();}
function fmt(mixed $n): string {return number_format((float)$n,2,'.',',');}
function csvesc(mixed $value): string {
 $v=(string)$value;return preg_match('/^\s*[=+@\-]/u',$v)? "'".$v : $v;
}
function audit(PDO $db,int $uid,string $event,?int $id=null): void {$s=$db->prepare('INSERT INTO audit_log(user_id,event_type,record_id) VALUES (?,?,?)');$s->execute([$uid,$event,$id]);}
function alert(string $text): string {return '<div class="alert alert-warning">'.e($text).'</div>';}
$page=(string)($_GET['page']??'dashboard');
if(!in_array($page,['login','dashboard','employees','runs','rules','team'],true)){$page='dashboard';}
$error='';$db=null;
try{
 if($_SERVER['REQUEST_METHOD']==='POST') {
  verify_csrf();$action=(string)($_POST['action']??'');
  if($action==='login'){
   $db=Database::connection();
   if(!login($db,post_string('email',190),(string)($_POST['password']??'')))throw new DomainException('Invalid credentials.');
   redirect('dashboard','Welcome back.');
  }
  $u=require_user();$uid=(int)$u['id'];$db=Database::connection();
  if($action==='logout'){logout();header('Location: ?page=login',true,303);exit;}
  if($action==='employee'){
   require_role(['admin','payroll']);
   $code=post_string('staff_code',40);$name=post_string('full_name',160);$dept=post_string('department',100);
   if($code===''||$name==='')throw new DomainException('Staff code and name are required.');
   $base=Payroll::money($_POST['base_salary']??'');
   $taxable=Payroll::money($_POST['taxable_allowance']??'0');
   $exempt=Payroll::money($_POST['exempt_allowance']??'0');
   $social=trim((string)($_POST['social_base']??''));$social=$social===''?null:Payroll::money($social);
   $s=$db->prepare('INSERT INTO employees(staff_code,full_name,department,base_salary,taxable_allowance,exempt_allowance,social_base) VALUES (?,?,?,?,?,?,?)');
   $s->execute([$code,$name,$dept,$base,$taxable,$exempt,$social]);audit($db,$uid,'employee.created',(int)$db->lastInsertId());redirect('employees','Employee registered.');
  }
  if($action==='toggle_employee'){
   require_role(['admin','payroll']);$id=(int)($_POST['id']??0);$active=(int)($_POST['active']??0);
   $s=$db->prepare('UPDATE employees SET active=? WHERE id=?');$s->execute([$active===1?1:0,$id]);audit($db,$uid,'employee.status.changed',$id);redirect('employees','Employee status updated.');
  }
  if($action==='generate'){
   require_role(['admin','payroll']);$period=Payroll::period(post_string('period',7));
   $db->beginTransaction();
   try{
    $settings=q_one($db,'SELECT * FROM settings WHERE id=1 FOR UPDATE');
    if(!$settings||(int)$settings['rules_verified']!==1)throw new DomainException('An administrator must verify payroll rules before processing salaries.');
    $year=(int)substr($period,0,4);
    if($year!==(int)$settings['tax_year'])throw new DomainException('Period year does not match configured tax year.');
    $brackets=q_all($db,'SELECT * FROM tax_brackets WHERE tax_year=? ORDER BY lower_bound',[$year]);
    if(count($brackets)<2)throw new DomainException('No tax brackets configured for this year.');
    $employees=q_all($db,'SELECT * FROM employees WHERE active=1 ORDER BY id');
    if(!$employees)throw new DomainException('No active employees.');
    $run=q_one($db,'SELECT * FROM payroll_runs WHERE period=? FOR UPDATE',[$period]);
    if($run&&(string)$run['status']==='final')throw new DomainException('Finalized payroll cannot be regenerated.');
    if(!$run){$s=$db->prepare('INSERT INTO payroll_runs(period,tax_year,created_by) VALUES(?,?,?)');$s->execute([$period,$year,$uid]);$runid=(int)$db->lastInsertId();}
    else{$runid=(int)$run['id'];$s=$db->prepare('DELETE FROM payroll_lines WHERE run_id=?');$s->execute([$runid]);}
    $insert=$db->prepare('INSERT INTO payroll_lines(run_id,employee_id,staff_code,employee_name,base_salary,taxable_allowance,exempt_allowance,social_base,gross,taxable_base,inss_employee,inss_employer,irt,net,employer_cost) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    foreach($employees as $e){
      $v=Payroll::calculate($e,$brackets,(float)$settings['inss_employee_rate'],(float)$settings['inss_employer_rate']);
      $insert->execute([$runid,$e['id'],$e['staff_code'],$e['full_name'],$v['base'],$v['taxable_allowance'],$v['exempt_allowance'],$v['social_base'],$v['gross'],$v['taxable_base'],$v['inss_employee'],$v['inss_employer'],$v['irt'],$v['net'],$v['employer_cost']]);
    }
    audit($db,$uid,'payroll.draft.generated',$runid);$db->commit();redirect('runs','Draft payroll generated. Review before finalizing.');
   }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
  }
  if($action==='finalize'){
   require_role(['admin']);$id=(int)($_POST['run_id']??0);
   $db->beginTransaction();try{
    $r=q_one($db,'SELECT * FROM payroll_runs WHERE id=? FOR UPDATE',[$id]);
    if(!$r||$r['status']!=='draft')throw new DomainException('Only draft runs can be finalized.');
    $s=$db->prepare("UPDATE payroll_runs SET status='final',finalized_by=?,finalized_at=NOW() WHERE id=?");$s->execute([$uid,$id]);audit($db,$uid,'payroll.finalized',$id);$db->commit();redirect('runs','Run finalized. It is now read-only.');
   }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
  }
  if($action==='settings'){
   require_role(['admin']);$name=post_string('company_name',190);$year=(int)($_POST['tax_year']??0);
   if($name===''||$year<2025||$year>2100)throw new DomainException('Invalid company name or tax year.');
   $employee=(float)($_POST['inss_employee_rate']??-1);$employer=(float)($_POST['inss_employer_rate']??-1);
   if(!is_finite($employee)||!is_finite($employer)||$employee<0||$employee>0.5||$employer<0||$employer>0.5)throw new DomainException('Enter contribution rates as fractions, e.g. 0.03 for 3%.');
   $s=$db->prepare('UPDATE settings SET company_name=?,tax_year=?,inss_employee_rate=?,inss_employer_rate=?,rules_verified=0,rules_notes=? WHERE id=1');
   $s->execute([$name,$year,$employee,$employer,post_string('rules_notes',1000)]);
   audit($db,$uid,'tax.settings.changed');redirect('rules','Settings saved. Reverification required.');
  }
  if($action==='bracket'){
   require_role(['admin']);$id=(int)($_POST['id']??0);$fixed=Payroll::money($_POST['fixed_amount']??'');$rate=(float)($_POST['rate']??-1);
   if(!is_finite($rate)||$rate<0||$rate>1)throw new DomainException('Invalid marginal rate (fraction 0 to 1).');
   $db->beginTransaction();try{
    $s=$db->prepare('UPDATE tax_brackets SET fixed_amount=?,rate=? WHERE id=?');$s->execute([$fixed,$rate,$id]);
    if($s->rowCount()!==1)throw new DomainException('Bracket not found or unchanged.');
    $db->exec('UPDATE settings SET rules_verified=0 WHERE id=1');audit($db,$uid,'tax.bracket.changed',$id);$db->commit();redirect('rules','Bracket changed. Reverification required.');
   }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
  }
  if($action==='verify_rules'){
   require_role(['admin']);
   if(($_POST['confirm']??'')!=='I HAVE VERIFIED')throw new DomainException('Type I HAVE VERIFIED to confirm independent legal review.');
   $s=$db->prepare('UPDATE settings SET rules_verified=1 WHERE id=1');$s->execute([]);audit($db,$uid,'tax.rules.verified');redirect('rules','Rules marked as reviewed.');
  }
  if($action==='add_user'){
   require_role(['admin']);$name=post_string('name',120);$email=post_string('email',190);$password=(string)($_POST['password']??'');$role=post_string('role',20);
   if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<12||!in_array($role,['admin','payroll','viewer'],true)||$name==='')throw new DomainException('Invalid user details; password must be 12+ characters.');
   $s=$db->prepare('INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,?)');$s->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$role]);audit($db,$uid,'user.created',(int)$db->lastInsertId());redirect('team','Team member created.');
  }
  throw new DomainException('Unknown action.');
 }
}catch(Throwable $e){$error=$e->getMessage();}
if($page==='login'||!current_user())$page='login';
if($page!=='login'&&$db===null){try{$db=Database::connection();}catch(Throwable $e){$error=$e->getMessage();}}
$u=current_user();$settings=$db&&$u?q_one($db,'SELECT * FROM settings WHERE id=1'):null;
$employees=$db&&$u?q_all($db,'SELECT * FROM employees ORDER BY active DESC,full_name'):[];
$runs=$db&&$u?q_all($db,'SELECT r.*,u.name AS creator,COUNT(l.id) AS employee_count,COALESCE(SUM(l.net),0) AS total_net,COALESCE(SUM(l.employer_cost),0) AS total_cost FROM payroll_runs r JOIN users u ON u.id=r.created_by LEFT JOIN payroll_lines l ON l.run_id=r.id GROUP BY r.id,u.name ORDER BY r.period DESC LIMIT 36'):[];
$brackets=$db&&$u&&$settings?q_all($db,'SELECT * FROM tax_brackets WHERE tax_year=? ORDER BY lower_bound',[(int)$settings['tax_year']]):[];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>AngolaPayroll · Open Source</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><style>
:root{color-scheme:dark}body{background:#0d1425;color:#e6ebfa;font-family:system-ui,-apple-system,'Segoe UI',sans-serif} .navbar,.panel{background:#172037;border:1px solid #27334e;border-radius:15px}.navbar{border-radius:0}.nav-link,.text-secondary{color:#a8b5d3!important}.nav-link.active{color:#fff!important}.panel{padding:1.3rem}.form-control,.form-select{background:#111a2d;color:#fff;border:1px solid #43506b}.form-control:focus,.form-select:focus{background:#111a2d;color:#fff}.table{--bs-table-bg:transparent;--bs-table-color:#e8ecfb;--bs-table-border-color:#39435a}.btn-primary{background:#7268ea;border-color:#7268ea}.muted{color:#a8b5d3}.big{font-size:1.9rem;font-weight:700}.badge-soft{background:#303b58;color:#e4eaff;border-radius:100px;padding:.25rem .75rem}.smallcap{font-size:.82rem;letter-spacing:.08em;text-transform:uppercase}.footer{color:#99a7cb;font-size:.84rem}.footer a{color:#b2b8fa}</style></head><body>
<nav class="navbar navbar-expand-lg navbar-dark"><div class="container"><a class="navbar-brand fw-bold" href="?page=dashboard">◈ AngolaPayroll</a><?php if($u):?><div class="d-flex flex-wrap gap-3 align-items-center"><a class="nav-link" href="?page=dashboard">Dashboard</a><a class="nav-link" href="?page=employees">Employees</a><a class="nav-link" href="?page=runs">Payroll runs</a><a class="nav-link" href="?page=rules">Tax settings</a><?php if($u['role']==='admin'):?><a class="nav-link" href="?page=team">Team</a><?php endif;?><form method="post" class="m-0"><?=csrf_field()?><input type="hidden" name="action" value="logout"><button class="btn btn-outline-light btn-sm">Sign out</button></form></div><?php endif;?></div></nav>
<main class="container py-4" style="min-height:80vh"><div class="mb-4"><div class="smallcap muted">Santos António · Open source business software</div><h1 class="fw-bold mt-2"><?=e(match($page){'login'=>'Secure sign in','employees'=>'Employee register','runs'=>'Payroll processing','rules'=>'Tax and INSS settings','team'=>'User access',default=>'Payroll dashboard'})?></h1><p class="muted mb-0">Indicative Angola Group A payroll · Independent legal review required · Not a certified statutory filing system.</p></div>
<?php if($error):?><div class="alert alert-danger" role="alert"><?=e($error)?></div><?php endif;?><?php if(!empty($_SESSION['flash'])):?><div class="alert alert-success"><?=e($_SESSION['flash'])?></div><?php unset($_SESSION['flash']);endif;?>
<?php if($page==='login'):?><div class="row justify-content-center"><div class="col-md-5"><div class="panel"><h2 class="h4">Sign in to your company</h2><form method="post"><?=csrf_field()?><input name="action" type="hidden" value="login"><label class="form-label">Email</label><input type="email" class="form-control mb-3" name="email" required><label class="form-label">Password</label><input type="password" class="form-control mb-4" name="password" required><button class="btn btn-primary w-100">Sign in</button></form><p class="small muted mt-3">First install? Create the administrator with scripts/create-admin.php.</p></div></div></div>
<?php elseif($page==='dashboard'):?>
<?php $totalNet=array_sum(array_map(fn($r)=>(float)$r['total_net'],array_slice($runs,0,1)));?>
<div class="row g-3 mb-4"><div class="col-md-4"><div class="panel"><div class="muted">Registered employees</div><div class="big"><?=count($employees)?></div></div></div><div class="col-md-4"><div class="panel"><div class="muted">Payroll runs</div><div class="big"><?=count($runs)?></div></div></div><div class="col-md-4"><div class="panel"><div class="muted">Latest net payroll (AOA)</div><div class="big"><?=fmt($totalNet)?></div></div></div></div>
<div class="panel mb-4"><h2 class="h5">Rules verification</h2><?php if(!$settings || !$settings['rules_verified']):?><?=alert('Not yet verified. Ask an authorized payroll specialist to validate the tax schedule, tax treatment of allowances and INSS bases before processing.')?><?php else:?><p class="text-success">Administrator confirmed local rule review. Confirm again whenever legislation or payroll policy changes.</p><?php endif;?><a href="?page=rules" class="btn btn-outline-light btn-sm">Review configuration</a></div>
<div class="panel"><h2 class="h5">Recent payroll runs</h2><div class="table-responsive"><table class="table"><thead><tr><th>Period</th><th>Status</th><th>Employees</th><th>Net total (AOA)</th></tr></thead><tbody><?php foreach(array_slice($runs,0,8) as $r):?><tr><td><?=e($r['period'])?></td><td><span class="badge-soft"><?=e($r['status'])?></span></td><td><?=e($r['employee_count'])?></td><td><?=fmt($r['total_net'])?></td></tr><?php endforeach;?></tbody></table></div></div>
<?php elseif($page==='employees'):?>
<div class="row g-4"><div class="col-xl-8"><div class="panel"><h2 class="h5">Employees</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Code</th><th>Name</th><th>Department</th><th>Base (AOA)</th><th>Status</th><th></th></tr></thead><tbody><?php foreach($employees as $e):?><tr><td><?=e($e['staff_code'])?></td><td><?=e($e['full_name'])?></td><td><?=e($e['department'])?></td><td><?=fmt($e['base_salary'])?></td><td><?=($e['active']?'Active':'Inactive')?></td><td><?php if(in_array($u['role'],['admin','payroll'],true)):?><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="toggle_employee"><input type="hidden" name="id" value="<?=(int)$e['id']?>"><input type="hidden" name="active" value="<?=$e['active']?'0':'1'?>"><button class="btn btn-outline-light btn-sm"><?=$e['active']?'Deactivate':'Activate'?></button></form><?php endif;?></td></tr><?php endforeach;?></tbody></table></div></div></div>
<?php if(in_array($u['role'],['admin','payroll'],true)):?><div class="col-xl-4"><div class="panel"><h2 class="h5">New employee</h2><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="employee"><label class="form-label">Staff code</label><input class="form-control mb-2" name="staff_code" required><label class="form-label">Full name</label><input class="form-control mb-2" name="full_name" required><label class="form-label">Department</label><input class="form-control mb-2" name="department"><label class="form-label">Base salary (AOA)</label><input type="number" min="0" step="0.01" class="form-control mb-2" name="base_salary" required><label class="form-label">Taxable monthly allowances</label><input type="number" min="0" step="0.01" class="form-control mb-2" name="taxable_allowance" value="0"><label class="form-label">Exempt/non-taxable allowances (validated)</label><input type="number" min="0" step="0.01" class="form-control mb-2" name="exempt_allowance" value="0"><label class="form-label">INSS assessment base (blank = base salary)</label><input type="number" min="0" step="0.01" class="form-control mb-3" name="social_base"><button class="btn btn-primary w-100">Add employee</button></form></div></div><?php endif;?></div>
<?php elseif($page==='runs'):?>
<?php if(in_array($u['role'],['admin','payroll'],true)):?><div class="panel mb-4"><h2 class="h5">Generate or refresh a draft run</h2><form method="post" class="row g-2 align-items-end"><?=csrf_field()?><input name="action" type="hidden" value="generate"><div class="col-md-4"><label class="form-label">Payroll month</label><input type="month" name="period" class="form-control" value="<?=date('Y-m')?>" required></div><div class="col-md-4"><button class="btn btn-primary w-100">Generate draft</button></div></form><div class="small muted mt-2">A draft is recalculated from current employee profiles and tax settings. Finalized runs are immutable.</div></div><?php endif;?>
<div class="panel"><h2 class="h5">Runs and CSV exports</h2><div class="table-responsive"><table class="table"><thead><tr><th>Period</th><th>Status</th><th>People</th><th>Net (AOA)</th><th>Employer cost (AOA)</th><th>Export</th><th></th></tr></thead><tbody><?php foreach($runs as $r):?><tr><td><?=e($r['period'])?></td><td><?=e($r['status'])?></td><td><?=e($r['employee_count'])?></td><td><?=fmt($r['total_net'])?></td><td><?=fmt($r['total_cost'])?></td><td><a class="btn btn-sm btn-outline-info" href="export.php?run=<?=(int)$r['id']?>">CSV</a></td><td><?php if($r['status']==='draft'&&$u['role']==='admin'):?><form method="post" onsubmit="return confirm('Finalize this run? This prevents further changes.')"><?=csrf_field()?><input type="hidden" name="action" value="finalize"><input type="hidden" name="run_id" value="<?=(int)$r['id']?>"><button class="btn btn-outline-warning btn-sm">Finalize</button></form><?php endif;?></td></tr><?php endforeach;?></tbody></table></div></div>
<?php elseif($page==='rules'):?>
<?php if(!$settings):?><?=alert('No settings found. Import database/schema.sql first.')?><?php else:?>
<div class="row g-4"><div class="col-xl-5"><div class="panel"><h2 class="h5">Company and contribution rates</h2><?php if($u['role']==='admin'):?><form method="post"><?=csrf_field()?><input name="action" type="hidden" value="settings"><label class="form-label">Company</label><input name="company_name" class="form-control mb-2" value="<?=e($settings['company_name'])?>" required><label class="form-label">Tax year</label><input type="number" min="2025" max="2100" name="tax_year" class="form-control mb-2" value="<?=e($settings['tax_year'])?>"><label class="form-label">Worker INSS rate (fraction)</label><input type="number" min="0" max=".5" step=".00001" name="inss_employee_rate" class="form-control mb-2" value="<?=e($settings['inss_employee_rate'])?>"><label class="form-label">Employer INSS rate (fraction)</label><input type="number" min="0" max=".5" step=".00001" name="inss_employer_rate" class="form-control mb-2" value="<?=e($settings['inss_employer_rate'])?>"><label class="form-label">Professional review notes</label><textarea name="rules_notes" class="form-control mb-3" rows="3"><?=e($settings['rules_notes'])?></textarea><button class="btn btn-primary">Save settings</button></form><?php else:?><p><?=e($settings['company_name'])?> · <?=e($settings['tax_year'])?></p><?php endif;?><hr><p class="muted small">Default rates 3% worker / 8% employer are for illustrative standard contributions; not every worker or earning component shares the same regime. Change only after professional review.</p></div></div>
<div class="col-xl-7"><div class="panel"><h2 class="h5">Group A IRT brackets · <?=e($settings['tax_year'])?></h2><?php if(!$brackets):?><?=alert('No brackets for selected year. Import an independently verified year-specific tax schedule before running payroll.')?><?php endif;?><div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Above (AOA)</th><th>Up to</th><th>Fixed</th><th>Rate</th><th>Save</th></tr></thead><tbody><?php foreach($brackets as $b):?><tr><td><?=fmt($b['lower_bound'])?></td><td><?=$b['upper_bound']===null?'∞':fmt($b['upper_bound'])?></td><?php if($u['role']==='admin'):?><td colspan="3"><form method="post" class="d-flex gap-2"><?=csrf_field()?><input name="action" type="hidden" value="bracket"><input name="id" type="hidden" value="<?=(int)$b['id']?>"><input class="form-control form-control-sm" type="number" min="0" step=".01" name="fixed_amount" aria-label="Fixed amount" value="<?=e($b['fixed_amount'])?>"><input class="form-control form-control-sm" type="number" min="0" max="1" step=".00001" name="rate" aria-label="Marginal rate" value="<?=e($b['rate'])?>"><button class="btn btn-sm btn-outline-primary">Save</button></form></td><?php else:?><td><?=fmt($b['fixed_amount'])?></td><td><?=e($b['rate'])?></td><td></td><?php endif;?></tr><?php endforeach;?></tbody></table></div></div></div></div>
<?php if($u['role']==='admin'):?><div class="panel mt-4"><h2 class="h5">Validate before payroll</h2><div class="alert alert-warning">The 2026 table is transcribed from Law 14/25 Annex I. Legal applicability, allowances, INSS basis, employee regimes and revisions must be reviewed independently before marking as verified. New years require verified brackets, not a blind copy of 2026.</div><form method="post" class="d-flex flex-wrap gap-2 align-items-end"><?=csrf_field()?><input name="action" type="hidden" value="verify_rules"><div><label class="form-label">Type I HAVE VERIFIED after review</label><input class="form-control" name="confirm" required></div><button class="btn btn-warning">Confirm review</button></form></div><?php endif;?>
<?php endif;?>
<?php elseif($page==='team'&&$u['role']==='admin'):?>
<div class="row g-4"><div class="col-md-7"><div class="panel"><h2 class="h5">Users</h2><table class="table"><thead><tr><th>Name</th><th>Email</th><th>Role</th></tr></thead><tbody><?php foreach(q_all($db,'SELECT name,email,role FROM users ORDER BY id') as $person):?><tr><td><?=e($person['name'])?></td><td><?=e($person['email'])?></td><td><?=e($person['role'])?></td></tr><?php endforeach;?></tbody></table></div></div><div class="col-md-5"><div class="panel"><h2 class="h5">New account</h2><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="add_user"><label class="form-label">Name</label><input name="name" class="form-control mb-2" required><label class="form-label">Email</label><input type="email" name="email" class="form-control mb-2" required><label class="form-label">Password · 12+ characters</label><input type="password" minlength="12" name="password" class="form-control mb-2" required><label class="form-label">Role</label><select name="role" class="form-select mb-3"><option value="viewer">Viewer</option><option value="payroll">Payroll operator</option><option value="admin">Administrator</option></select><button class="btn btn-primary">Create user</button></form></div></div></div>
<?php else:?><?=alert('Access denied.')?><?php endif;?></main><footer class="footer text-center pb-4">AngolaPayroll · MIT open source · <a href="https://santosantonio.it.ao/" target="_blank" rel="noopener noreferrer">Santos António</a> · Optional support: RedotPay 1612817131 · Airtm santosantonio.eng@gmail.com</footer><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html>
