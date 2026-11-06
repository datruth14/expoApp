<?php
declare(strict_types=1);
$publicProgram = [
    'slug' => $program['slug'],
    'eventName' => $program['name'],
    'platformTitle' => $program['platform_title'],
    'tagline' => $program['tagline'],
    'welcome' => $program['welcome_message'],
    'primary' => $program['primary_color'],
    'accent' => $program['accent_color'],
    'services' => $program['services'],
    'signedIn' => (bool) $delegate,
    'initialArea' => $requestedArea,
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="assets/loading.css">
<script defer src="assets/loading.js"></script>
<meta name="description" content="<?= h($program['description']) ?>">
<title><?= h($program['name']) ?> · <?= h($program['platform_title']) ?></title>
<style>
:root{--navy:<?= h($program['primary_color']) ?>;--blue:#2677c8;--gold:<?= h($program['accent_color']) ?>;--ink:#17345e;--muted:#627895;--paper:#fff;--bg:#f2f7ff;--accent:<?= h($program['accent_color']) ?>}
*{box-sizing:border-box}body{margin:0;font-family:Arial,Helvetica,sans-serif;color:var(--ink);background:linear-gradient(140deg,#fff 0%,var(--bg) 58%,#fff8e9 100%)}button,input,textarea,select{font:inherit}button{cursor:pointer}.wrap{max-width:1160px;margin:auto;padding:18px}.topbar{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;padding:8px 0 18px}.wordmark{font-size:17px;font-weight:950;letter-spacing:.3px;color:var(--navy)}.wordmark small{display:block;font-size:10px;letter-spacing:1.7px;color:#b17b1e;margin-top:4px}nav{display:flex;gap:8px;flex-wrap:wrap}nav button,.btn{border:1px solid #d6e1ef;background:#fff;color:#17447e;border-radius:11px;padding:10px 14px;font-weight:850;box-shadow:0 3px 8px #183b6810}nav button.active{background:linear-gradient(135deg,#246fc0,#153f84);color:#fff;border-color:#174b91;box-shadow:0 4px 0 #10366e}.btn.primary{background:linear-gradient(135deg,#ffe6a0,#e5aa30);color:#18345e;border:1px solid #d6a13a;box-shadow:0 4px 0 #a97920}.hero{position:relative;overflow:hidden;border-radius:25px;padding:clamp(18px,3vw,34px);background:radial-gradient(circle at 12% 10%,#fff2c5 0,transparent 28%),linear-gradient(135deg,#fff,#edf5ff 68%,#fff7e6);border:1px solid #dce7f5;box-shadow:0 10px 26px #1b4a8018}.hero-inner{position:relative;z-index:1;text-align:center}.event-logo{display:block;width:min(740px,100%);height:auto;max-height:390px;object-fit:contain;margin:0 auto 12px;filter:drop-shadow(0 8px 14px #17365c18);border-radius:12px}.event-logo.hidden{display:none}.rule{height:5px;max-width:600px;margin:12px auto 18px;border-radius:8px;background:linear-gradient(90deg,var(--gold),#ffe7a1,#1b5db0,#ffe7a1,var(--gold))}.event-title{font-size:clamp(28px,4.5vw,46px);font-weight:1000;line-height:1.08;color:var(--navy);margin:10px 0;text-shadow:0 2px #fff,0 4px 10px #163f761c}.event-subtitle{font-size:clamp(11px,1.7vw,15px);font-weight:900;letter-spacing:2px;color:#b17b1e;margin:9px 0 13px}.event-welcome{max-width:720px;margin:0 auto 18px;color:#526b8b;font-weight:650;line-height:1.55}.section-title{font-size:clamp(22px,3vw,29px);font-weight:1000;color:#174887;margin:28px 0 6px}.section-sub{font-size:13px;color:var(--muted);font-weight:650;margin:0 0 17px}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.service{min-height:104px;padding:14px;display:flex;align-items:center;gap:12px;text-align:left;color:white;border:1px solid #ffffffb8;border-radius:15px;box-shadow:0 5px 0 #183d73,0 10px 18px #173e7020;position:relative;overflow:hidden;transition:transform .18s}.service:hover{transform:translateY(-3px)}.service:after{content:"";position:absolute;width:100px;height:100px;right:-38px;bottom:-55px;border-radius:50%;background:radial-gradient(circle at 30% 30%,#ffffff75,transparent 70%)}.service .ico{width:48px;height:48px;min-width:48px;border-radius:14px;display:grid;place-items:center;font-size:27px;background:#ffffff27;border:1px solid #ffffff55}.service strong{display:block;font-size:15px;font-weight:950;line-height:1.25}.service .service-name{position:relative;z-index:1}.c1{background:linear-gradient(130deg,#2385d1,#17458f)}.c2{background:linear-gradient(130deg,#edb23b,#b66b20)}.c3{background:linear-gradient(130deg,#20aaa0,#08717b)}.c4{background:linear-gradient(130deg,#7867d6,#3b4d9e)}.c5{background:linear-gradient(130deg,#d85c9c,#883b91)}.c6{background:linear-gradient(130deg,#43a0d7,#24528c)}.c7{background:linear-gradient(130deg,#dda634,#a65c25)}.c8{background:linear-gradient(130deg,#38a77b,#17624e)}.c9{background:linear-gradient(130deg,#6988d9,#334887)}.quick{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:22px}.quick button{border:1px solid #d9e5f3;background:white;border-radius:14px;padding:16px;text-align:left;color:#174887;font-weight:900;box-shadow:0 5px 14px #163e7010}.quick span{display:block;font-size:22px;margin-bottom:6px}.panel{background:#fff;border:1px solid #dce6f2;border-radius:18px;padding:20px;box-shadow:0 8px 22px #17365d12}.hidden{display:none!important}label{display:block;font-size:13px;font-weight:850;margin:13px 0 6px;color:#183d70}input:not([type=checkbox]),select,textarea{width:100%;padding:12px;border:1px solid #cbd9eb;border-radius:10px;background:#f9fbff;color:#173463}input[type=checkbox]{width:auto}textarea{resize:vertical}.stars button{font-size:28px;color:#c8cfda;border:0;background:transparent;padding:2px}.stars button.sel{color:#e9ad30}.actions{display:flex;gap:9px;flex-wrap:wrap;margin-top:16px}.notice{margin-top:14px;padding:12px;border-radius:10px;background:#fff7df;border:1px solid #f0d48c;color:#71521b;font-size:12px;line-height:1.5}.service-detail{padding:22px;background:white;border:1px solid #dce6f2;border-radius:17px;box-shadow:0 8px 22px #17365d12}.mini-title{font-size:21px;font-weight:950;color:#174887;margin:0 0 9px}footer{text-align:center;padding:28px 5px 12px;color:#72819a;font-size:11px}footer a{color:#72819a}@media(max-width:600px){.wrap{padding:11px}.grid{gap:10px}.service{min-height:98px;padding:10px;gap:8px}.service .ico{width:40px;height:40px;min-width:40px;font-size:23px}.service strong{font-size:12px}.quick{gap:8px}.quick button{padding:12px;font-size:12px}nav button{font-size:10px;padding:9px}}
</style>
</head>
<body>
<div class="wrap" id="app">
 <header class="topbar">
  <div class="wordmark"><span id="platformTitle"><?= h($program['platform_title']) ?></span><small id="topEventLabel"><?= h($program['name']) ?></small></div>
  <nav aria-label="Main navigation">
   <button class="active" data-page="home">HOME</button>
  </nav>
 </header>
 <section id="home">
  <div class="hero"><div class="hero-inner">
   <?php if (!empty($program['banner_path'])): ?><img id="coverLogo" class="event-logo" src="<?= h($program['banner_path']) ?>" alt="<?= h($program['name']) ?> banner"><?php endif; ?>
   <div class="rule"></div>
   <h1 class="event-title" id="coverTitle"><?= h($program['platform_title']) ?></h1>
   <div class="event-subtitle" id="coverEvent"><?= h($program['name']) ?><?= $program['tagline'] !== '' ? ' · ' . h($program['tagline']) : '' ?></div>
   <p class="event-welcome" id="coverWelcome"><?= h($program['welcome_message']) ?></p>
   <button class="btn primary" id="jumpServices">VIEW SERVICE AREAS&nbsp; →</button>
  </div></div>
  <div id="services">
   <h2 class="section-title">SERVICE AREAS</h2>
   <p class="section-sub">Select a service area to view details or share feedback.</p>
   <div class="grid" id="serviceGrid"></div>
  </div>
 </section>
 <section id="area" class="hidden">
  <button class="btn" data-back="home">← BACK TO SERVICE AREAS</button>
  <div class="service-detail" style="margin-top:16px">
   <h2 class="mini-title" id="areaTitle">SERVICE AREA</h2><p class="section-sub" id="areaDescription"></p>
   <button class="btn primary" id="areaFeedback">RATE THIS SERVICE →</button>
  </div>
 </section>
 <section id="feedback" class="hidden">
  <h2 class="section-title">DELEGATE FEEDBACK</h2><p class="section-sub">Your feedback helps the event team improve the delegate experience.</p>
  <div class="panel<?= $delegate ? ' hidden' : '' ?>" id="delegateGate">
   <h3>Sign in to share feedback</h3>
   <p id="delegateGateMessage">Continue with KingsChat to verify your account, then return to this service’s feedback form.</p>
   <a class="btn primary" id="delegateSignin" href="<?= h(delegate_login_url($program['slug'], $requestedArea ?: 'Overall event experience')) ?>" style="display:inline-block;text-decoration:none">Continue with KingsChat</a>
  </div>
  <?php if ($delegate): ?>
  <div class="panel" style="margin-bottom:14px">
   <p>Signed in with KingsChat as <strong>@<?= h($delegate['handle']) ?></strong></p>
   <form method="post" action="delegate-logout.php">
    <input type="hidden" name="csrf_token" value="<?= h($feedbackCsrf) ?>">
    <button class="btn" type="submit">Sign out of KingsChat on this site</button>
   </form>
  </div>
  <?php endif; ?>
  <form class="panel<?= $delegate ? '' : ' hidden' ?>" id="feedbackForm" action="submit-feedback.php" method="post">
   <input type="hidden" name="program_slug" value="<?= h($program['slug']) ?>">
   <input type="hidden" name="csrf_token" value="<?= h($feedbackCsrf) ?>">
   <label for="feedbackArea">Service area</label><select id="feedbackArea" name="area" required></select>
   <label>How would you rate this service?</label><div class="stars" id="stars" role="group" aria-label="Service rating"><button type="button" data-star="1" aria-label="1 star">★</button><button type="button" data-star="2" aria-label="2 stars">★</button><button type="button" data-star="3" aria-label="3 stars">★</button><button type="button" data-star="4" aria-label="4 stars">★</button><button type="button" data-star="5" aria-label="5 stars">★</button></div><input type="hidden" name="rating" id="rating" value="">
   <label for="positive">What went well?</label><textarea id="positive" name="positive" rows="3" maxlength="10000" placeholder="Tell us what you appreciated..."></textarea>
   <label for="improve">Comments or areas for improvement</label><textarea id="improve" name="comments" rows="4" maxlength="10000" placeholder="Tell us what could be improved..."></textarea>
   <label style="display:flex;align-items:center;gap:8px"><input id="anonymous" name="anonymous" type="checkbox"> Hide my name and KingsChat handle in feedback reports</label>
   <p class="section-sub">Your sign-in remains linked internally; selecting this option hides your identity in admin feedback views and CSV exports.</p>
   <div class="actions"><button class="btn primary" id="submitFeedback" type="submit">SUBMIT FEEDBACK</button><button class="btn" type="reset">CLEAR FORM</button></div><p id="formMessage" class="section-sub" role="status" aria-live="polite"></p>
  </form>
 </section>
  <footer><em><?= h($program['platform_title']) ?></em> · <?= h($program['name']) ?></footer>
</div>
<script src="assets/vendor/sweetalert2.all.min.js"></script>
<script>
const PROGRAM=<?= json_encode($publicProgram, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
(function(){
 const root=document.getElementById('app');let rating=0;
 const $=id=>document.getElementById(id);
 const pages=['home','area','feedback'];
 function safe(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
 function show(page){pages.forEach(p=>$(p).classList.toggle('hidden',p!==page));root.querySelectorAll('nav button').forEach(b=>b.classList.toggle('active',b.dataset.page===page));if(page==='feedback')updateSignInLink();window.scrollTo({top:0,behavior:'smooth'});}
 function updateSignInLink(){const query=new URLSearchParams({program:PROGRAM.slug,area:$('feedbackArea').value||'Overall event experience'});$('delegateSignin').href='delegate-login.php?'+query.toString();}
 root.querySelectorAll('[data-page]').forEach(b=>b.addEventListener('click',()=>show(b.dataset.page)));
 root.querySelectorAll('[data-back]').forEach(b=>b.addEventListener('click',()=>show(b.dataset.back)));
 $('serviceGrid').innerHTML=PROGRAM.services.map((service,i)=>`<button class="service c${i%9+1}" data-service="${i}"><span class="ico">${safe(service[2]||'✦')}</span><span class="service-name"><strong>${safe(service[0])}</strong></span></button>`).join('');
 $('serviceGrid').querySelectorAll('[data-service]').forEach(button=>button.addEventListener('click',()=>{const service=PROGRAM.services[+button.dataset.service];$('areaTitle').textContent=service[0];$('areaDescription').textContent=service[1];$('feedbackArea').value=service[0];show('area');}));
 $('feedbackArea').innerHTML='<option value="">Select service area</option>'+PROGRAM.services.map(s=>`<option value="${safe(s[0])}">${safe(s[0])}</option>`).join('')+'<option value="Overall event experience">Overall event experience</option>';
 $('feedbackArea').value=PROGRAM.initialArea;
 if(PROGRAM.initialArea)show('feedback');
 $('jumpServices').addEventListener('click',()=>$('services').scrollIntoView({behavior:'smooth'}));
 $('areaFeedback').addEventListener('click',()=>show('feedback'));
 root.querySelectorAll('[data-star]').forEach(button=>button.addEventListener('click',()=>{rating=+button.dataset.star;$('rating').value=String(rating);root.querySelectorAll('[data-star]').forEach(star=>star.classList.toggle('sel',+star.dataset.star<=rating));}));
 $('feedbackForm').addEventListener('reset',()=>{rating=0;$('rating').value='';root.querySelectorAll('[data-star]').forEach(star=>star.classList.remove('sel'));$('formMessage').textContent='';});
 $('feedbackForm').addEventListener('submit',async event=>{
  event.preventDefault();
  if(!PROGRAM.signedIn){show('feedback');return;}
  if(!$('feedbackArea').value||!rating){$('formMessage').textContent='Please select a service area and star rating.';return;}
  const form=event.currentTarget;const button=$('submitFeedback');button.disabled=true;$('formMessage').textContent='Sending your feedback…';
  window.UIFeedback?.start(button);
   try{
    const response=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{Accept:'application/json'}});const result=await response.json();
    if(response.ok&&result.ok){
     form.reset();
     const message=result.message||'Thank you. Your feedback has been recorded.';
     if(window.Swal){Swal.fire({toast:true,position:'top-end',icon:'success',titleText:message,showConfirmButton:false,showCloseButton:true,timer:5000,timerProgressBar:true,didOpen:toast=>{toast.addEventListener('mouseenter',Swal.stopTimer);toast.addEventListener('mouseleave',Swal.resumeTimer);}});}
     else{$('formMessage').textContent=message;}
    }else{
     $('formMessage').textContent=result.message||'Unable to submit feedback.';
     if(response.status===401){PROGRAM.signedIn=false;$('feedbackForm').classList.add('hidden');$('delegateGate').classList.remove('hidden');$('delegateGateMessage').textContent=result.message;updateSignInLink();}
    }
   }
  catch(error){$('formMessage').textContent='Unable to submit right now. Please try again.';}
  finally{button.disabled=false;window.UIFeedback?.stop(button);}
 });
})();
</script>
</body>
</html>
