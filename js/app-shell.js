(function(){
    function ready(fn){ if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn); else fn(); }
    ready(function(){
      var offline = document.getElementById('offline-banner');
      function setOnline(on){ if(!offline) return; offline.hidden = on; offline.setAttribute('aria-hidden', on ? 'true' : 'false'); }
      window.addEventListener('online', function(){ setOnline(true); });
      window.addEventListener('offline', function(){ setOnline(false); });
      setOnline(navigator.onLine !== false);

      var installBtn = document.getElementById('install-btn');
      var deferred;
      window.addEventListener('beforeinstallprompt', function(e){
        e.preventDefault();
        deferred = e;
        if(installBtn) installBtn.hidden = false;
      });
      if(installBtn){
        installBtn.addEventListener('click', function(){
          if(!deferred) return;
          deferred.prompt();
          deferred.userChoice.finally(function(){ deferred = null; installBtn.hidden = true; });
        });
      }
      window.addEventListener('appinstalled', function(){ deferred = null; if(installBtn) installBtn.hidden = true; });

      var printBtn = document.getElementById('print-btn');
      if(printBtn) printBtn.addEventListener('click', function(){ window.print(); });

      var pngBtn = document.getElementById('png-btn');
      if(pngBtn) pngBtn.addEventListener('click', function(){
        if (typeof html2canvas !== 'function') { alert('html2canvas is not loaded.'); return; }
        var card = document.querySelector('.card');
        if (!card) return;
        pngBtn.disabled = true;
        html2canvas(card, { scale: 2, useCORS: true, backgroundColor: null }).then(function(canvas){
          try {
            var link = document.createElement('a');
            link.download = 'business-card.png';
            link.href = canvas.toDataURL('image/png');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
          } catch (e) { console.error(e); alert('Export failed.'); }
        }).catch(function(e){ console.error(e); alert('Export failed.'); })
          .finally(function(){ pngBtn.disabled = false; });
      });

      if(!('serviceWorker' in navigator)) return;
      navigator.serviceWorker.register('sw.js').then(function(reg){
        function promptUpdate(worker){
          if(!worker) return;
          worker.postMessage({ type: 'SKIP_WAITING' });
        }
        if(reg.waiting) promptUpdate(reg.waiting);
        reg.addEventListener('updatefound', function(){
          var nw = reg.installing;
          if(!nw) return;
          nw.addEventListener('statechange', function(){
            if(nw.state === 'installed' && navigator.serviceWorker.controller){
              promptUpdate(nw);
            }
          });
        });
        var refreshing = false;
        navigator.serviceWorker.addEventListener('controllerchange', function(){
          if(refreshing) return;
          refreshing = true;
          window.location.reload();
        });
      }).catch(function(){});
    });
  })();
