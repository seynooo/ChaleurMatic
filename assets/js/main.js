/* ============================================================
   ChaleurMatic – main.js (multi-pages)
   ============================================================ */

/* ── Bouton téléphone flottant ── */
(function () {
  var btn = document.createElement('a');
  btn.href = 'tel:+32488274228';
  btn.id = 'float-call-btn';
  btn.innerHTML = '<span class="fcb-icon">📞</span><span class="fcb-text">+32 488 27 42 28</span>';
  btn.setAttribute('aria-label', 'Appeler ChaleurMatic');
  document.body.appendChild(btn);

  // Vibration toutes les 2 secondes
  setInterval(function () {
    btn.classList.remove('fcb-shake');
    void btn.offsetWidth;
    btn.classList.add('fcb-shake');
  }, 2000);
})();

/* ── Burger menu mobile ── */
function toggleNav() {
  const nav    = document.getElementById('navLinks');
  const burger = document.querySelector('.burger');
  const isOpen = nav.classList.toggle('open');

  // Croix sur le burger
  burger.classList.toggle('open', isOpen);

  // Bloquer scroll
  document.body.style.overflow = isOpen ? 'hidden' : '';
}

// Fermer en cliquant sur un lien (sauf dropdown toggle)
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.nav-links a:not(.nav-dropdown-toggle)').forEach(function(link) {
    link.addEventListener('click', function() {
      const nav = document.getElementById('navLinks');
      if (nav.classList.contains('open')) toggleNav();
    });
  });
});

/* ── Brands strip : drag / swipe ── */
document.addEventListener('DOMContentLoaded', function () {
  var track = document.querySelector('.brands-track');
  if (!track) return;

  var isDragging = false;
  var startX = 0;
  var currentOffset = 0;
  var animOffset = 0;

  function getCurrentTranslateX() {
    var style = window.getComputedStyle(track);
    var matrix = new DOMMatrix(style.transform);
    return matrix.m41;
  }

  function startDrag(x) {
    isDragging = true;
    animOffset = getCurrentTranslateX();
    track.style.animation = 'none';
    track.style.transform = 'translateX(' + animOffset + 'px)';
    currentOffset = animOffset;
    startX = x;
  }

  function moveDrag(x) {
    if (!isDragging) return;
    var delta = x - startX;
    currentOffset = animOffset + delta;
    track.style.transform = 'translateX(' + currentOffset + 'px)';
  }

  function endDrag() {
    if (!isDragging) return;
    isDragging = false;
    track.style.animation = '';
    track.style.transform = '';
  }

  // Touch
  track.addEventListener('touchstart',  function(e) { startDrag(e.touches[0].clientX); }, { passive: true });
  track.addEventListener('touchmove',   function(e) { moveDrag(e.touches[0].clientX); },  { passive: true });
  track.addEventListener('touchend',    endDrag);

  // Mouse
  track.addEventListener('mousedown',   function(e) { startDrag(e.clientX); e.preventDefault(); });
  window.addEventListener('mousemove',  function(e) { moveDrag(e.clientX); });
  window.addEventListener('mouseup',    endDrag);
});

/* ── Page erreur : onglets marques ── */
function switchBrand(brand) {
  document.querySelectorAll('.err-tab').forEach(function(t) { t.classList.remove('active'); });
  document.querySelectorAll('.err-panel').forEach(function(p) { p.classList.remove('active'); });
  var tab = document.querySelector('.err-tab[data-brand="' + brand + '"]');
  var panel = document.getElementById('panel-' + brand);
  if (tab) tab.classList.add('active');
  if (panel) panel.classList.add('active');
}

/* ── FAQ accordéon (mobile) ── */
document.addEventListener('DOMContentLoaded', function () {
  if (window.innerWidth <= 768) {
    document.querySelectorAll('.faq-item').forEach(function(item) {
      item.addEventListener('click', function() {
        const isOpen = item.classList.contains('open');
        document.querySelectorAll('.faq-item').forEach(function(i) { i.classList.remove('open'); });
        if (!isOpen) item.classList.add('open');
      });
    });
  }
});

/* ── Tarif cards : voir plus (mobile) ── */
document.addEventListener('DOMContentLoaded', function () {
  if (window.innerWidth <= 480) {
    document.querySelectorAll('.tarif-card').forEach(function(card) {
      const desc = card.querySelector('.tarif-desc');
      if (!desc) return;
      const btn = document.createElement('button');
      btn.className = 'voir-plus-btn';
      btn.textContent = '+ Voir plus';
      desc.insertAdjacentElement('afterend', btn);
      btn.addEventListener('click', function() {
        card.classList.toggle('expanded');
        btn.textContent = card.classList.contains('expanded') ? '− Voir moins' : '+ Voir plus';
      });
    });
  }
});

/* ── Dropdown Services ── */
function toggleDropdown(e) {
  e.preventDefault();
  e.stopPropagation();
  const wrap = document.getElementById('nav-services-wrap');
  const isOpen = wrap.classList.contains('open');
  closeDropdown();
  if (!isOpen) {
    wrap.classList.add('open');
    document.getElementById('nav-arrow-srv').style.transform = 'rotate(180deg)';
  }
}

function closeDropdown() {
  const wrap = document.getElementById('nav-services-wrap');
  if (wrap) {
    wrap.classList.remove('open');
    const arrow = document.getElementById('nav-arrow-srv');
    if (arrow) arrow.style.transform = 'rotate(0deg)';
  }
}

document.addEventListener('click', function(e) {
  const wrap = document.getElementById('nav-services-wrap');
  if (wrap && !wrap.contains(e.target)) closeDropdown();
});

/* ── Dropdown hover avec délai de fermeture ── */
document.addEventListener('DOMContentLoaded', function () {
  const wrap = document.getElementById('nav-services-wrap');
  if (!wrap) return;

  let closeTimer = null;

  // Ouvrir au survol
  wrap.addEventListener('mouseenter', function () {
    clearTimeout(closeTimer);
    wrap.classList.add('open');
    const arrow = document.getElementById('nav-arrow-srv');
    if (arrow) arrow.style.transform = 'rotate(180deg)';
  });

  // Fermer avec délai de 300ms après que la souris quitte
  wrap.addEventListener('mouseleave', function () {
    closeTimer = setTimeout(function () {
      wrap.classList.remove('open');
      const arrow = document.getElementById('nav-arrow-srv');
      if (arrow) arrow.style.transform = 'rotate(0deg)';
    }, 300);
  });
});

/* ── Marquer le lien actif selon la page courante ── */
document.addEventListener('DOMContentLoaded', function () {
  const path = window.location.pathname;
  document.querySelectorAll('.nav-links a').forEach(function(a) {
    const href = a.getAttribute('href');
    if (href && path.endsWith(href)) {
      a.classList.add('active');
    }
  });
});

/* ── Formulaire RDV (stepper) ── */
const COMMUNES = [
  'Bruxelles-Ville','Ixelles','Etterbeek','Uccle','Forest',
  'Anderlecht','Molenbeek','Saint-Gilles','Jette','Koekelberg',
  'Laeken','Neder-Over-Heembeek','Woluwe-Saint-Lambert',
  'Woluwe-Saint-Pierre','Auderghem','Watermael-Boitsfort',
  'Schaerbeek','Evere','Ganshoren','Berchem-Sainte-Agathe'
];

function buildCommunes() {
  const wrap = document.getElementById('communesWrap');
  if (!wrap) return;
  COMMUNES.forEach(function(name) {
    const el = document.createElement('span');
    el.className = 'commune-tag';
    el.textContent = name;
    wrap.appendChild(el);
  });
}

document.addEventListener('DOMContentLoaded', buildCommunes);

/* ── Stepper RDV ── */
let currentStep = 1;
const TOTAL_STEPS = 3;

function stepNext(from) {
  if (from === 1) {
    const checked = document.querySelector('input[name="service_rdv"]:checked');
    if (!checked) { alert('Veuillez choisir un service.'); return; }
  }
  if (from === 2) {
    const prenom  = document.getElementById('s2-prenom')?.value.trim();
    const tel     = document.getElementById('s2-tel')?.value.trim();
    const adresse = document.getElementById('s2-adresse')?.value.trim();
    const email   = document.getElementById('s2-email')?.value.trim();
    if (!prenom || !tel || !adresse) {
      alert('Veuillez remplir les champs obligatoires (Prénom, Téléphone, Adresse).');
      return;
    }
    if (!email) {
      alert('Veuillez renseigner votre email pour recevoir la confirmation de rendez-vous.');
      return;
    }
  }
  goToStep(from + 1);
}

function stepBack(from) { goToStep(from - 1); }

function goToStep(step) {
  document.getElementById('step-panel-' + currentStep)?.classList.remove('active');
  const prevDot = document.getElementById('step-dot-' + currentStep);
  if (prevDot) {
    prevDot.classList.remove('active');
    if (step > currentStep) prevDot.classList.add('done');
    else                     prevDot.classList.remove('done');
  }
  for (let i = 1; i < TOTAL_STEPS; i++) {
    document.getElementById('step-line-' + i)?.classList.toggle('done', i < step);
  }
  currentStep = step;
  document.getElementById('step-panel-' + currentStep)?.classList.add('active');
  const dot = document.getElementById('step-dot-' + currentStep);
  if (dot) { dot.classList.remove('done'); dot.classList.add('active'); }
}

function onCreneauChange() {
  const section = document.getElementById('cal-section');
  if (section) {
    section.style.display = 'block';
    section.style.animation = 'fadeUp .3s ease both';
  }
}

function submitStepper() {
  const creneau = document.querySelector('input[name="creneau_rdv"]:checked')?.value || '';
  if (!creneau) { alert('Veuillez choisir un créneau horaire.'); return; }
  const dateVal = document.getElementById('s3-date')?.value;
  if (!dateVal) { alert('Veuillez sélectionner une date.'); return; }
  const serviceEl = document.querySelector('input[name="service_rdv"]:checked');
  const prenom    = document.getElementById('s2-prenom')?.value.trim();
  const nom       = document.getElementById('s2-nom')?.value.trim();
  const tel       = document.getElementById('s2-tel')?.value.trim();
  const email     = document.getElementById('s2-email')?.value.trim();
  const adresse   = document.getElementById('s2-adresse')?.value.trim();
  const date      = document.getElementById('s3-date')?.value;
  const message   = document.getElementById('s3-message')?.value.trim();

  // Chaudière
  const typeChaudiere = document.getElementById('s2-type')?.value;
  const marque        = document.getElementById('s2-marque')?.value;
  const modele        = document.getElementById('s2-modele')?.value.trim();
  const annee         = document.getElementById('s2-annee')?.value.trim();

  // Désactiver le bouton pendant l'envoi
  const btnSend = document.querySelector('.btn-send');
  if (btnSend) { btnSend.disabled = true; btnSend.textContent = 'Envoi en cours…'; }

  const data = new FormData();
  data.append('service',        serviceEl ? serviceEl.value : '');
  data.append('prenom',         prenom    || '');
  data.append('nom',            nom       || '');
  data.append('tel',            tel       || '');
  data.append('email',          email     || '');
  data.append('adresse',        adresse   || '');
  data.append('date',           date      || '');
  data.append('creneau',        creneau   || '');
  data.append('message',        message   || '');
  data.append('type_chaudiere', typeChaudiere || '');
  data.append('marque',         marque    || '');
  data.append('modele',         modele    || '');
  data.append('annee',          annee     || '');

  fetch('send.php', { method: 'POST', body: data })
    .then(function(res) { return res.json(); })
    .then(function(json) {
      if (json.success) {
        // Afficher la confirmation
        document.getElementById('step-panel-3')?.classList.remove('active');
        for (let i = 1; i <= TOTAL_STEPS; i++) {
          const d = document.getElementById('step-dot-' + i);
          if (d) { d.classList.remove('active'); d.classList.add('done'); }
        }
        for (let i = 1; i < TOTAL_STEPS; i++) {
          document.getElementById('step-line-' + i)?.classList.add('done');
        }
        const recap = document.getElementById('confirm-recap');
        if (recap) {
          recap.innerHTML =
            '<strong>Service</strong>' + (serviceEl ? serviceEl.value : '—') + '<br>' +
            '<strong>Nom</strong>' + prenom + ' ' + nom + '<br>' +
            '<strong>Téléphone</strong>' + tel + '<br>' +
            (email   ? '<strong>Email</strong>' + email + '<br>' : '') +
            '<strong>Adresse</strong>' + adresse + '<br>' +
            (typeChaudiere ? '<strong>Chaudière</strong>' + typeChaudiere + (marque ? ' · ' + marque : '') + (modele ? ' · ' + modele : '') + '<br>' : '') +
            (date ? '<strong>Date souhaitée</strong>' + date + (creneau ? ' · ' + creneau : '') + '<br>' : '');
        }
        document.getElementById('step-panel-confirm')?.classList.add('active');
      } else {
        alert('Une erreur est survenue. Veuillez nous appeler directement au +32 488 27 42 28.');
        if (btnSend) { btnSend.disabled = false; btnSend.textContent = 'Envoyer la demande ✓'; }
      }
    })
    .catch(function() {
      alert('Une erreur est survenue. Veuillez nous appeler directement au +32 488 27 42 28.');
      if (btnSend) { btnSend.disabled = false; btnSend.textContent = 'Envoyer la demande ✓'; }
    });
}

function resetStepper() {
  document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
  for (let i = 1; i <= TOTAL_STEPS; i++) {
    const d = document.getElementById('step-dot-' + i);
    if (d) d.classList.remove('active', 'done');
  }
  for (let i = 1; i < TOTAL_STEPS; i++) {
    document.getElementById('step-line-' + i)?.classList.remove('done');
  }
  document.querySelectorAll('input[name="service_rdv"]').forEach(r => r.checked = false);
  ['s2-prenom','s2-nom','s2-tel','s2-email','s2-adresse','s3-date','s3-message'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
  document.querySelectorAll('input[name="creneau_rdv"]').forEach(r => r.checked = false);
  currentStep = 1;
  document.getElementById('step-panel-1')?.classList.add('active');
  document.getElementById('step-dot-1')?.classList.add('active');
  calReset();
}

/* ── Calendrier custom (step 3) ── */
(function () {
  const MOIS = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
  const today = new Date();
  today.setHours(0,0,0,0);

  let calYear  = today.getFullYear();
  let calMonth = today.getMonth();
  let selectedDate    = null;
  let selectedDateISO = null;
  let blockedSlots = { weekdays: [], creneau_global: [], dates: {} };

  function pad(n) { return String(n).padStart(2,'0'); }

  // Vérifie si une date (objet Date) est entièrement bloquée
  function isDateBlocked(cellDate) {
    const jsDay  = cellDate.getDay();
    const monDay = (jsDay === 0) ? 6 : jsDay - 1; // 0=Lun … 6=Dim
    if (blockedSlots.weekdays.includes(monDay)) return true;
    const iso = cellDate.getFullYear() + '-' + pad(cellDate.getMonth()+1) + '-' + pad(cellDate.getDate());
    const dateSlots = blockedSlots.dates[iso];
    if (dateSlots && dateSlots.includes(null)) return true;
    return false;
  }

  // Met à jour l'affichage des créneaux selon la date sélectionnée
  function updateCreneaux(iso) {
    const globalBlocked = blockedSlots.creneau_global || [];
    const dateBlocked   = (iso && blockedSlots.dates[iso])
      ? blockedSlots.dates[iso].filter(c => c !== null)
      : [];
    const allBlocked = [...globalBlocked, ...dateBlocked];

    document.querySelectorAll('input[name="creneau_rdv"]').forEach(function(radio) {
      const val = radio.value;
      let blocked = false;
      if (allBlocked.includes('Matin')      && val.startsWith('Matin'))      blocked = true;
      if (allBlocked.includes('Après-midi') && val.startsWith('Après-midi')) blocked = true;

      radio.disabled = blocked;
      const inner = radio.nextElementSibling;
      if (inner) inner.classList.toggle('cr-blocked', blocked);

      if (blocked && radio.checked) {
        radio.checked = false;
        // Si le calendrier était visible et qu'on déselectionne le créneau, pas besoin de cacher
      }
    });
  }

  function renderCal() {
    const grid  = document.getElementById('cal-grid');
    const label = document.getElementById('cal-month-label');
    if (!grid || !label) return;

    label.textContent = MOIS[calMonth] + ' ' + calYear;

    const firstDay   = new Date(calYear, calMonth, 1).getDay();
    const startCol   = (firstDay === 0) ? 6 : firstDay - 1;
    const daysInMonth = new Date(calYear, calMonth + 1, 0).getDate();

    grid.innerHTML = '';

    for (let i = 0; i < startCol; i++) {
      const empty = document.createElement('div');
      empty.className = 'cal-cell cal-empty';
      grid.appendChild(empty);
    }

    for (let d = 1; d <= daysInMonth; d++) {
      const cell = document.createElement('div');
      cell.className = 'cal-cell';
      cell.textContent = d;

      const cellDate = new Date(calYear, calMonth, d);
      cellDate.setHours(0,0,0,0);
      const iso = calYear + '-' + pad(calMonth + 1) + '-' + pad(d);

      if (cellDate < today) {
        cell.classList.add('cal-past');
      } else if (isDateBlocked(cellDate)) {
        cell.classList.add('cal-blocked');
      } else {
        if (cellDate.getTime() === today.getTime()) cell.classList.add('cal-today');
        if (selectedDate && cellDate.getTime() === selectedDate.getTime()) cell.classList.add('cal-selected');

        cell.addEventListener('click', function () {
          selectedDate    = cellDate;
          selectedDateISO = iso;
          const hiddenInput = document.getElementById('s3-date');
          if (hiddenInput) hiddenInput.value = iso;
          const lbl = document.getElementById('cal-selected-label');
          if (lbl) {
            lbl.textContent = 'Date choisie : ' + pad(d) + '/' + pad(calMonth + 1) + '/' + calYear;
            lbl.classList.add('has-date');
          }
          updateCreneaux(iso);
          renderCal();
        });
      }

      grid.appendChild(cell);
    }
  }

  function calReset() {
    calYear         = today.getFullYear();
    calMonth        = today.getMonth();
    selectedDate    = null;
    selectedDateISO = null;
    const lbl = document.getElementById('cal-selected-label');
    if (lbl) { lbl.textContent = 'Aucune date sélectionnée'; lbl.classList.remove('has-date'); }
    const section = document.getElementById('cal-section');
    if (section) section.style.display = 'none';
    updateCreneaux(null);
    renderCal();
  }

  window.calReset = calReset;

  document.addEventListener('DOMContentLoaded', function () {
    if (!document.getElementById('cal-grid')) return;

    // Charger les bloquages depuis l'API
    fetch('get_blocked.php')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        blockedSlots = data;
        renderCal();
        updateCreneaux(null);
      })
      .catch(function() { renderCal(); });

    const prev = document.getElementById('cal-prev');
    const next = document.getElementById('cal-next');
    if (prev) prev.addEventListener('click', function () {
      calMonth--;
      if (calMonth < 0) { calMonth = 11; calYear--; }
      const nowYear = today.getFullYear(), nowMonth = today.getMonth();
      if (calYear < nowYear || (calYear === nowYear && calMonth < nowMonth)) {
        calMonth = nowMonth; calYear = nowYear;
      }
      renderCal();
    });
    if (next) next.addEventListener('click', function () {
      calMonth++;
      if (calMonth > 11) { calMonth = 0; calYear++; }
      renderCal();
    });
  });
})();
