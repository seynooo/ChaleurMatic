/* ============================================================
   Chaleurmatic – intro.js  |  Flame Ignition Splash Screen
   ============================================================ */
(function () {
  if (sessionStorage.getItem('intro_done')) return;

  /* ── CSS ─────────────────────────────────────────────────── */
  const S = document.createElement('style');
  S.textContent = `
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    #cmi{
      position:fixed;inset:0;z-index:99999;
      background:#000;display:flex;align-items:center;
      justify-content:center;overflow:hidden;
      font-family:'Barlow Condensed',sans-serif;
    }
    /* canvas plein écran */
    #cmi-canvas{position:absolute;inset:0;width:100%;height:100%;}

    /* Halo radial derrière la flamme */
    #cmi-halo{
      position:absolute;
      width:600px;height:600px;
      border-radius:50%;
      background:radial-gradient(circle,
        rgba(255,120,0,.0) 0%,
        rgba(255,60,0,.0)  30%,
        transparent 70%);
      transform:scale(0);
      pointer-events:none;
      transition:none;
    }

    /* Wrapper flamme SVG */
    #cmi-flame{
      position:relative;z-index:3;
      opacity:0;transform:scale(.3) translateY(80px);
      filter:drop-shadow(0 0 0px #e8185a);
      transition:none;
    }
    #cmi-flame svg{width:160px;height:200px;}

    /* Texte brand */
    #cmi-text{
      position:absolute;z-index:4;
      bottom:22%;left:50%;transform:translateX(-50%);
      text-align:center;
      opacity:0;
    }
    #cmi-text h1{
      font-size:clamp(3rem,10vw,6rem);
      font-weight:900;text-transform:uppercase;
      letter-spacing:.05em;color:#f5f0eb;
      line-height:1;
      text-shadow:0 0 40px rgba(232,24,90,.8),0 0 80px rgba(249,115,22,.4);
    }
    #cmi-text h1 span{color:#e8185a;}
    #cmi-text p{
      font-size:clamp(.7rem,2.5vw,.95rem);
      letter-spacing:.3em;text-transform:uppercase;
      color:#7a7580;margin-top:.5rem;
    }

    /* Barre progress */
    #cmi-bar{
      position:absolute;bottom:0;left:0;right:0;
      height:3px;background:rgba(255,255,255,.05);z-index:5;
    }
    #cmi-bar-fill{
      height:100%;width:0;
      background:linear-gradient(90deg,#e8185a,#f97316,#fbbf24);
      box-shadow:0 0 12px #e8185a, 0 0 30px rgba(249,115,22,.5);
    }

    /* Sortie */
    @keyframes cmiOut{
      0%  {opacity:1;transform:scale(1);}
      50% {opacity:1;transform:scale(1.06);}
      100%{opacity:0;transform:scale(1.15);}
    }
    #cmi.out{animation:cmiOut .8s cubic-bezier(.4,0,.6,1) forwards;}

    /* Crack lines */
    .cmi-crack{
      position:absolute;z-index:2;
      background:linear-gradient(var(--a),rgba(255,100,0,.0),rgba(255,180,50,.7),rgba(255,100,0,.0));
      width:2px;transform-origin:bottom center;
      opacity:0;
    }
  `;
  document.head.appendChild(S);

  /* ── DOM ──────────────────────────────────────────────────── */
  const el = document.createElement('div'); el.id='cmi';
  el.innerHTML = `
    <canvas id="cmi-canvas"></canvas>
    <div id="cmi-halo"></div>

    <div id="cmi-flame">
      <svg viewBox="0 0 160 200" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <radialGradient id="ig1" cx="50%" cy="85%" r="55%">
            <stop offset="0%"   stop-color="#ffffff"/>
            <stop offset="15%"  stop-color="#fff7c0"/>
            <stop offset="35%"  stop-color="#fbbf24"/>
            <stop offset="60%"  stop-color="#f97316"/>
            <stop offset="85%"  stop-color="#e8185a"/>
            <stop offset="100%" stop-color="#7f1d1d" stop-opacity="0"/>
          </radialGradient>
          <radialGradient id="ig2" cx="50%" cy="90%" r="40%">
            <stop offset="0%"   stop-color="#ffffff" stop-opacity=".95"/>
            <stop offset="50%"  stop-color="#fde68a" stop-opacity=".6"/>
            <stop offset="100%" stop-color="#f97316" stop-opacity="0"/>
          </radialGradient>
          <radialGradient id="ig3" cx="50%" cy="85%" r="55%">
            <stop offset="0%"   stop-color="#e8185a" stop-opacity=".4"/>
            <stop offset="100%" stop-color="transparent"/>
          </radialGradient>
          <filter id="iblur"><feGaussianBlur stdDeviation="3"/></filter>
          <filter id="iblur2"><feGaussianBlur stdDeviation="6"/></filter>
        </defs>
        <!-- Aura extérieure floue -->
        <path d="M80,8 C80,8 118,45 122,80 C126,108 116,125 110,138
                 C118,118 122,98 112,78 C128,100 126,135 110,152
                 C118,130 114,105 96,88 C108,112 104,140 80,158
                 C56,140 52,112 64,88 C46,105 42,130 50,152
                 C34,135 32,100 48,78 C38,98 42,118 50,138
                 C44,125 34,108 38,80 C42,45 80,8 80,8Z"
          fill="url(#ig3)" filter="url(#iblur2)" opacity=".8"/>
        <!-- Corps principal -->
        <path d="M80,12 C80,12 115,48 118,78 C121,102 112,118 107,130
                 C114,112 116,94 108,74 C122,96 120,128 106,144
                 C112,124 109,102 94,86 C104,108 101,134 80,150
                 C59,134 56,108 66,86 C51,102 48,124 54,144
                 C40,128 38,96 52,74 C44,94 46,112 53,130
                 C48,118 39,102 42,78 C45,48 80,12 80,12Z"
          fill="url(#ig1)"/>
        <!-- Flamme intérieure -->
        <path d="M80,35 C80,35 100,58 102,76 C104,89 98,98 95,105
                 C100,92 101,80 96,68 C104,82 103,100 95,112
                 C98,98 96,82 86,72 C93,88 91,108 80,120
                 C69,108 67,88 74,72 C64,82 62,98 65,112
                 C57,100 56,82 64,68 C59,80 60,92 65,105
                 C62,98 56,89 58,76 C60,58 80,35 80,35Z"
          fill="url(#ig2)" filter="url(#iblur)" opacity=".9"/>
        <!-- Cœur blanc pur -->
        <ellipse cx="80" cy="128" rx="12" ry="16"
          fill="white" opacity=".7" filter="url(#iblur)"/>
      </svg>
    </div>

    <div id="cmi-text">
      <h1>Chaleur<span>matic</span></h1>
      <p>Chauffagiste · Bruxelles · 7j/7</p>
    </div>

    <div id="cmi-bar"><div id="cmi-bar-fill"></div></div>
  `;
  document.body.prepend(el);
  document.body.style.overflow='hidden';

  /* ── Canvas particules ────────────────────────────────────── */
  const canvas = document.getElementById('cmi-canvas');
  const ctx    = canvas.getContext('2d');
  let   W, H, cx, cy, particles=[], embers=[], sparks=[];
  let   flamePhase = 0; // 0=rien 1=allumage 2=flamme pleine

  function resize(){
    W=canvas.width=window.innerWidth;
    H=canvas.height=window.innerHeight;
    cx=W/2; cy=H/2;
  }
  resize();
  window.addEventListener('resize',resize);

  /* Particule de fumée/chaleur initiale */
  function makeSmoke(){
    return {
      x:cx+(Math.random()-.5)*10,
      y:cy+60,
      vx:(Math.random()-.5)*.4,
      vy:-(Math.random()*1.2+.3),
      life:1,decay:Math.random()*.012+.006,
      r:Math.random()*18+8,
      hue:Math.random()<.5?0:20,
    };
  }

  /* Étincelle d'allumage */
  function makeSpark(phase){
    const a=Math.random()*Math.PI*2;
    const spd=(phase===1?4:2)*(Math.random()+.5);
    return {
      x:cx+(Math.random()-.5)*20,
      y:cy+40+(Math.random()-.5)*20,
      vx:Math.cos(a)*spd,
      vy:Math.sin(a)*spd-2,
      life:1,decay:Math.random()*.04+.02,
      r:Math.random()*3+1,
      color:Math.random()<.6?'#fbbf24':Math.random()<.5?'#f97316':'#e8185a',
    };
  }

  /* Braise flottante */
  function makeEmber(){
    return {
      x:cx+(Math.random()-.5)*60,
      y:cy+20+(Math.random()-.5)*30,
      vx:(Math.random()-.5)*1.5,
      vy:-(Math.random()*2+1),
      life:1,decay:Math.random()*.008+.004,
      r:Math.random()*2.5+.5,
      color:`hsl(${Math.random()*30+5},100%,${Math.random()*30+55}%)`,
      wobble:Math.random()*Math.PI*2,
    };
  }

  /* Anneau d'onde de choc */
  let shockwaves=[];
  function makeShockwave(){
    shockwaves.push({r:0,maxR:Math.max(W,H)*.8,life:1,decay:.018});
  }

  /* ── Rendu canvas ──────────────────────────────────────────── */
  let frame=0;
  function draw(){
    ctx.clearRect(0,0,W,H);

    /* Fond radial chaleur */
    if(flamePhase>=1){
      const heat=flamePhase===1?frame/40:1;
      const gr=ctx.createRadialGradient(cx,cy+40,0,cx,cy+40,Math.min(W,H)*.55*heat);
      gr.addColorStop(0,`rgba(255,60,0,${.12*heat})`);
      gr.addColorStop(.4,`rgba(232,24,90,${.06*heat})`);
      gr.addColorStop(1,'rgba(0,0,0,0)');
      ctx.fillStyle=gr;
      ctx.fillRect(0,0,W,H);
    }

    /* Ondes de choc */
    shockwaves=shockwaves.filter(s=>{
      s.r+=18; s.life-=s.decay;
      if(s.life<=0)return false;
      ctx.beginPath();
      ctx.arc(cx,cy+40,s.r,0,Math.PI*2);
      ctx.strokeStyle=`rgba(255,180,60,${s.life*.25})`;
      ctx.lineWidth=3*s.life;
      ctx.stroke();
      return true;
    });

    /* Fumée */
    particles=particles.filter(p=>{
      p.x+=p.vx; p.y+=p.vy;
      p.life-=p.decay; p.r+=.3;
      if(p.life<=0)return false;
      ctx.beginPath();
      ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
      ctx.fillStyle=`hsla(${p.hue},80%,40%,${p.life*.18})`;
      ctx.fill();
      return true;
    });

    /* Étincelles */
    sparks=sparks.filter(s=>{
      s.vx*=.96; s.vy+=.12; s.vy*=.97;
      s.x+=s.vx; s.y+=s.vy;
      s.life-=s.decay;
      if(s.life<=0)return false;
      ctx.beginPath();
      ctx.arc(s.x,s.y,s.r,0,Math.PI*2);
      ctx.fillStyle=s.color.replace(')',`,${s.life})`).replace('rgb','rgba');
      ctx.shadowBlur=8; ctx.shadowColor=s.color;
      ctx.fill();
      ctx.shadowBlur=0;
      return true;
    });

    /* Braises */
    embers=embers.filter(e=>{
      e.wobble+=.06;
      e.vx+=Math.sin(e.wobble)*.05;
      e.x+=e.vx; e.y+=e.vy;
      e.life-=e.decay;
      if(e.life<=0)return false;
      ctx.beginPath();
      ctx.arc(e.x,e.y,e.r,0,Math.PI*2);
      ctx.fillStyle=e.color;
      ctx.globalAlpha=e.life*.9;
      ctx.shadowBlur=6; ctx.shadowColor=e.color;
      ctx.fill();
      ctx.globalAlpha=1; ctx.shadowBlur=0;
      return true;
    });

    frame++;
    requestAnimationFrame(draw);
  }
  draw();

  /* ── Flamme SVG refs ─────────────────────────────────────── */
  const flameEl = document.getElementById('cmi-flame');
  const haloEl  = document.getElementById('cmi-halo');
  const textEl  = document.getElementById('cmi-text');
  const barFill = document.getElementById('cmi-bar-fill');

  /* ── Timeline d'animation ────────────────────────────────── */

  /* Phase 0 — 0ms : fumée légère */
  for(let i=0;i<8;i++) particles.push(makeSmoke());
  const smokeInt=setInterval(()=>{
    if(flamePhase<2) particles.push(makeSmoke());
  },120);

  /* Phase 1 — 400ms : IGNITION — explosion d'étincelles */
  setTimeout(()=>{
    flamePhase=1;
    /* Burst d'étincelles */
    for(let i=0;i<60;i++) sparks.push(makeSpark(1));
    /* Onde de choc */
    makeShockwave();
    /* Halo explose */
    haloEl.style.transition='transform .5s cubic-bezier(.2,1.6,.4,1), background .8s ease';
    haloEl.style.transform='scale(1)';
    haloEl.style.background='radial-gradient(circle, rgba(255,160,0,.25) 0%, rgba(232,24,90,.12) 40%, transparent 70%)';
    /* Flamme surgit */
    flameEl.style.transition='opacity .15s ease, transform .6s cubic-bezier(.2,1.8,.3,1), filter 1.2s ease';
    flameEl.style.opacity='1';
    flameEl.style.transform='scale(1.15) translateY(-10px)';
    flameEl.style.filter='drop-shadow(0 0 30px #e8185a) drop-shadow(0 0 60px #f97316) drop-shadow(0 0 100px rgba(255,100,0,.5))';
  },400);

  /* Étincelles continues */
  let sparkInt=setInterval(()=>{
    if(flamePhase>=1){
      for(let i=0;i<3;i++) sparks.push(makeSpark(2));
      for(let i=0;i<2;i++) embers.push(makeEmber());
    }
  },60);

  /* Phase 2 — 800ms : flamme se stabilise + oscillation */
  setTimeout(()=>{
    flamePhase=2;
    flameEl.style.transition='transform 1.4s ease, filter 1s ease';
    flameEl.style.transform='scale(1) translateY(0)';
    flameEl.style.filter='drop-shadow(0 0 20px #e8185a) drop-shadow(0 0 50px #f97316) drop-shadow(0 0 80px rgba(255,100,0,.4))';

    /* Oscillation organique de la flamme */
    let t=0;
    const flicker=setInterval(()=>{
      t+=.08;
      const sx=1+Math.sin(t*1.3)*.025+Math.sin(t*2.7)*.015;
      const sy=1+Math.cos(t*1.1)*.03 +Math.cos(t*2.3)*.02;
      const ty=Math.sin(t*.9)*4;
      const blur1=18+Math.sin(t*1.5)*6;
      const blur2=45+Math.cos(t*1.2)*10;
      flameEl.style.transform=`scale(${sx},${sy}) translateY(${ty}px)`;
      flameEl.style.filter=`drop-shadow(0 0 ${blur1}px #e8185a) drop-shadow(0 0 ${blur2}px #f97316)`;
      if(flamePhase>=3) clearInterval(flicker);
    },30);

    /* Braises denses */
    clearInterval(sparkInt);
    sparkInt=setInterval(()=>{
      for(let i=0;i<4;i++) embers.push(makeEmber());
      sparks.push(makeSpark(2));
    },40);

    /* Deuxième shockwave légère */
    setTimeout(makeShockwave,200);
  },800);

  /* Phase 3 — 1200ms : texte apparaît */
  setTimeout(()=>{
    flamePhase=3;
    textEl.style.transition='opacity .6s ease, transform .6s cubic-bezier(.2,1.4,.4,1)';
    textEl.style.opacity='1';
    textEl.style.transform='translateX(-50%) translateY(0)';
    /* Barre de progression */
    barFill.style.transition='width 1.1s cubic-bezier(.4,0,.2,1)';
    barFill.style.width='100%';
  },1200);

  /* Phase 4 — 2600ms : SORTIE */
  setTimeout(()=>{
    clearInterval(smokeInt);
    clearInterval(sparkInt);
    flamePhase=4;
    el.classList.add('out');
    setTimeout(()=>{
      el.remove();
      document.body.style.overflow='';
      sessionStorage.setItem('intro_done','1');
    },800);
  },2600);

})();
