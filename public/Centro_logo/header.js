// JS opcional para fecha dinámica y pequeños detalles de accesibilidad
(function(){
  const today = new Date();
  const dd = String(today.getDate()).padStart(2,'0');
  const mm = String(today.getMonth()+1).padStart(2,'0');
  const yyyy = today.getFullYear();
  const el = document.getElementById('today');
  if(el){ el.textContent = `${dd}/${mm}/${yyyy}`; }

  // Permitir navegación por teclado entre tiles
  const tiles = Array.from(document.querySelectorAll('.tile'));
  tiles.forEach(t => t.setAttribute('tabindex','0'));

  let idx = 0;
  document.addEventListener('keydown', (e) => {
    if(!['ArrowRight','ArrowLeft','ArrowDown','ArrowUp'].includes(e.key)) return;
    e.preventDefault();
    const cols = getComputedStyle(document.querySelector('.grid')).gridTemplateColumns.split(' ').length;
    if(e.key==='ArrowRight') idx = (idx + 1) % tiles.length;
    if(e.key==='ArrowLeft')  idx = (idx - 1 + tiles.length) % tiles.length;
    if(e.key==='ArrowDown')  idx = Math.min(idx + cols, tiles.length - 1);
    if(e.key==='ArrowUp')    idx = Math.max(idx - cols, 0);
    tiles[idx].focus();
  });
})();