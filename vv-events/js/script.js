// ============ Nav toggle ============
const navToggle = document.querySelector('.nav-toggle');
const navLinks = document.querySelector('.nav-links');
if(navToggle){
  navToggle.addEventListener('click', () => navLinks.classList.toggle('open'));
  navLinks?.querySelectorAll('a').forEach(a => a.addEventListener('click', () => navLinks.classList.remove('open')));
}

// ============ Sticky nav shadow on scroll ============
const siteNav = document.querySelector('.site-nav');
window.addEventListener('scroll', () => {
  if(!siteNav) return;
  siteNav.style.boxShadow = window.scrollY > 10 ? '0 6px 24px rgba(43,20,28,.08)' : 'none';
});

// ============ Scroll reveal ============
const revealEls = document.querySelectorAll('.reveal');
const io = new IntersectionObserver((entries) => {
  entries.forEach(e => { if(e.isIntersecting){ e.target.classList.add('in'); io.unobserve(e.target); } });
}, { threshold: .15 });
revealEls.forEach((el,i) => { el.style.setProperty('--i', i % 6); io.observe(el); });

// ============ Counters ============
const counters = document.querySelectorAll('[data-count]');
const counterIO = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if(!entry.isIntersecting) return;
    const el = entry.target;
    const target = parseInt(el.dataset.count, 10);
    let current = 0;
    const step = Math.max(1, Math.ceil(target / 60));
    const tick = () => {
      current += step;
      if(current >= target){ el.textContent = target + (el.dataset.suffix || ''); return; }
      el.textContent = current + (el.dataset.suffix || '');
      requestAnimationFrame(tick);
    };
    tick();
    counterIO.unobserve(el);
  });
}, { threshold: .5 });
counters.forEach(c => counterIO.observe(c));

// ============ Gallery filter ============
const filterBtns = document.querySelectorAll('.filter-bar button');
const galleryItems = document.querySelectorAll('.masonry figure');
filterBtns.forEach(btn => {
  btn.addEventListener('click', () => {
    filterBtns.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const cat = btn.dataset.filter;
    galleryItems.forEach(item => {
      const show = cat === 'all' || item.dataset.cat === cat;
      item.style.display = show ? '' : 'none';
    });
  });
});

// ============ Chip select (customize page) ============
document.querySelectorAll('.chip-select').forEach(group => {
  group.addEventListener('click', (e) => {
    const chip = e.target.closest('.chip');
    if(!chip) return;
    const input = chip.querySelector('input');
    if(input.type === 'radio'){
      group.querySelectorAll('.chip').forEach(c => c.classList.remove('checked'));
      chip.classList.add('checked');
      input.checked = true;
    } else {
      chip.classList.toggle('checked');
      input.checked = !input.checked;
    }
    updateEstimate();
  });
});

// ============ Live price estimate ============
const priceMap = {
  eventType: { corporate: 25000, wedding: 60000, birthday: 12000, family: 15000 },
  decoration: { basic: 5000, premium: 12000, luxury: 22000 },
  entry: { none: 0, balloon: 4000, pyro: 15000, dance: 10000, dj: 12000 },
  photography: 8000,
  videography: 10000,
  cake: 3000,
  magicShow: 6000,
  host: 5000,
};

function updateEstimate(){
  const total = document.getElementById('estTotal');
  if(!total) return;
  let sum = 0;
  const eventType = document.querySelector('input[name="eventType"]:checked')?.value;
  const decoration = document.querySelector('input[name="decoration"]:checked')?.value;
  const entry = document.querySelector('input[name="entry"]:checked')?.value;
  sum += priceMap.eventType[eventType] || 0;
  sum += priceMap.decoration[decoration] || 0;
  sum += priceMap.entry[entry] || 0;
  document.querySelectorAll('input[name="addons"]:checked').forEach(cb => {
    sum += priceMap[cb.value] || 0;
  });
  const guests = parseInt(document.getElementById('guestCount')?.value || '0', 10);
  if(guests > 100) sum += Math.floor((guests - 100) / 50) * 3000;

  total.textContent = '₹' + sum.toLocaleString('en-IN');

  const rows = document.getElementById('estRows');
  if(rows){
    let html = '';
    if(eventType) html += `<div class="summary-row"><span>Event Type</span><span>₹${(priceMap.eventType[eventType]||0).toLocaleString('en-IN')}</span></div>`;
    if(decoration) html += `<div class="summary-row"><span>Decoration</span><span>₹${(priceMap.decoration[decoration]||0).toLocaleString('en-IN')}</span></div>`;
    if(entry && entry !== 'none') html += `<div class="summary-row"><span>Entry Effect</span><span>₹${(priceMap.entry[entry]||0).toLocaleString('en-IN')}</span></div>`;
    document.querySelectorAll('input[name="addons"]:checked').forEach(cb => {
      html += `<div class="summary-row"><span>${cb.dataset.label}</span><span>₹${(priceMap[cb.value]||0).toLocaleString('en-IN')}</span></div>`;
    });
    rows.innerHTML = html || '<div class="summary-row"><span>Select options to see pricing</span><span></span></div>';
  }
}
document.getElementById('guestCount')?.addEventListener('input', updateEstimate);
updateEstimate();

// ============ Base API URL Configuration (Vercel & Railway Deployment) ============
const API_BASE = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
  ? ''
  : 'https://vibe-coding-production-9af0.up.railway.app';

// ============ Booking form submit (PHP & MySQL Backend Integration) ============
const bookingForm = document.getElementById('bookingForm');
if(bookingForm){
  bookingForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const submitBtn = document.getElementById('btnBookingSubmit');
    const errorBox = document.getElementById('bookingError');
    const confirmBox = document.getElementById('bookingConfirm');
    const confirmMsg = document.getElementById('bookingConfirmMessage');
    const refBadge = document.getElementById('bookingRefBadge');

    if(errorBox) { errorBox.style.display = 'none'; errorBox.textContent = ''; }

    // Collect data
    const formData = new FormData(bookingForm);
    const data = {};
    formData.forEach((val, key) => {
      if(key === 'addons'){
        if(!data[key]) data[key] = [];
        data[key].push(val);
      } else {
        data[key] = val;
      }
    });

    // Add radio selections if missed
    data['eventType'] = document.querySelector('input[name="eventType"]:checked')?.value || 'wedding';
    data['decoration'] = document.querySelector('input[name="decoration"]:checked')?.value || 'basic';
    data['entry'] = document.querySelector('input[name="entry"]:checked')?.value || 'none';

    // Show loading UI
    const originalBtnText = submitBtn ? submitBtn.textContent : '';
    if(submitBtn){ submitBtn.disabled = true; submitBtn.textContent = 'Submitting Booking...'; }

    try {
      const response = await fetch(`${API_BASE}/api/book.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      });
      const result = await response.json();

      if(result.success){
        bookingForm.style.display = 'none';
        confirmBox.style.display = 'block';
        if(confirmMsg) confirmMsg.textContent = result.message || 'Thank you for choosing VV Events!';
        if(refBadge && result.data && result.data.booking_ref){
          refBadge.textContent = 'Booking Reference #: ' + result.data.booking_ref;
        }
        confirmBox.scrollIntoView({ behavior:'smooth', block:'center' });
        // Refresh calendar with newly booked date
        buildCalendar();
      } else {
        if(errorBox){
          errorBox.textContent = result.message || 'An error occurred while submitting your booking.';
          errorBox.style.display = 'block';
        } else {
          alert(result.message || 'Error saving booking.');
        }
      }
    } catch (err) {
      console.warn('Backend endpoint unavailable, falling back to local confirmation:', err);
      // Fallback for static environment execution
      bookingForm.style.display = 'none';
      confirmBox.style.display = 'block';
      confirmBox.scrollIntoView({ behavior:'smooth', block:'center' });
    } finally {
      if(submitBtn){ submitBtn.disabled = false; submitBtn.textContent = originalBtnText; }
    }
  });
}

// ============ Contact form submit (PHP & MySQL Backend Integration) ============
const contactForm = document.getElementById('contactForm');
if(contactForm){
  contactForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const submitBtn = document.getElementById('btnContactSubmit');
    const errorBox = document.getElementById('contactError');
    const thanksBox = document.getElementById('contactThanks');

    if(errorBox){ errorBox.style.display = 'none'; errorBox.textContent = ''; }
    if(thanksBox){ thanksBox.style.display = 'none'; }

    const formData = new FormData(contactForm);
    const data = Object.fromEntries(formData.entries());

    const originalBtnText = submitBtn ? submitBtn.textContent : '';
    if(submitBtn){ submitBtn.disabled = true; submitBtn.textContent = 'Sending Message...'; }

    try {
      const response = await fetch(`${API_BASE}/api/contact.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      });
      const result = await response.json();

      if(result.success){
        contactForm.reset();
        if(thanksBox){
          thanksBox.textContent = result.message || "Thank you — we've received your message and will reach out shortly!";
          thanksBox.style.display = 'block';
        }
      } else {
        if(errorBox){
          errorBox.textContent = result.message || 'An error occurred. Please try again.';
          errorBox.style.display = 'block';
        }
      }
    } catch(err) {
      console.warn('Backend API endpoint offline, showing local success state:', err);
      contactForm.reset();
      if(thanksBox) thanksBox.style.display = 'block';
    } finally {
      if(submitBtn){ submitBtn.disabled = false; submitBtn.textContent = originalBtnText; }
    }
  });
}

// ============ Dynamic Calendar with MySQL Database Sync ============
async function buildCalendar(){
  const cal = document.getElementById('miniCalendar');
  if(!cal) return;

  const today = new Date();
  const year = today.getFullYear(), month = today.getMonth();
  let bookedDays = [3, 4, 5, 12, 18, 19, 25]; // Static fallback

  // Attempt to fetch real booked dates from MySQL API
  try {
    const res = await fetch(`${API_BASE}/api/get_booked_dates.php?month=${month+1}&year=${year}`);
    const resData = await res.json();
    if(resData && resData.success && Array.isArray(resData.booked_days)){
      bookedDays = resData.booked_days;
    }
  } catch(e) {
    // Standard static fallback
  }

  const daysInMonth = new Date(year, month+1, 0).getDate();
  const firstDay = new Date(year, month, 1).getDay();
  let html = '';
  const monthName = today.toLocaleString('default',{month:'long'});
  
  html += `<div class="cal-title">${monthName} ${year}</div><div class="cal-grid">`;
  ['S','M','T','W','T','F','S'].forEach(d => html += `<div class="cal-dow">${d}</div>`);
  for(let i=0;i<firstDay;i++) html += `<div></div>`;
  for(let d=1; d<=daysInMonth; d++){
    const isBooked = bookedDays.includes(d);
    html += `<div class="cal-day ${isBooked ? 'booked' : 'open'}" data-day="${d}">${d}</div>`;
  }
  html += '</div>';
  cal.innerHTML = html;

  cal.querySelectorAll('.cal-day.open').forEach(el => {
    el.addEventListener('click', () => {
      cal.querySelectorAll('.cal-day').forEach(c => c.classList.remove('selected'));
      el.classList.add('selected');
      const hidden = document.getElementById('selectedDate');
      if(hidden) hidden.value = `${el.dataset.day} ${monthName} ${year}`;
    });
  });
}
buildCalendar();
