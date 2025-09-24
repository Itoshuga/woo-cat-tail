(function(){
  function onReady(fn){
    if(document.readyState !== 'loading'){ fn(); }
    else { document.addEventListener('DOMContentLoaded', fn); }
  }

  function moveWrap(){
    var wrap = document.getElementById('ims-bottom-el-wrap');
    if(!wrap) return false;

    var selector = (window.CatTailConfig && CatTailConfig.selector) || '.content-wrapper';
    var position = (window.CatTailConfig && CatTailConfig.position) || 'afterend';
    var target = document.querySelector(selector);

    if(target && target.parentNode){
      try{
        // évite de déplacer 50x
        if(!wrap.dataset.moved){
          target.insertAdjacentElement(position, wrap);
          wrap.dataset.moved = '1';
        }
        return true;
      }catch(e){
        try{
          target.insertAdjacentElement('afterend', wrap);
          wrap.dataset.moved = '1';
          return true;
        }catch(_) {}
      }
    }
    return false;
  }

  onReady(function(){
    // 1) tentative immédiate
    if (moveWrap()) return;

    // 2) petites tentatives espacées (pour les thèmes lents)
    var tries = 0;
    var iv = setInterval(function(){
      tries++;
      if (moveWrap() || tries >= 10) clearInterval(iv);
    }, 200);

    // 3) MutationObserver : si le conteneur arrive plus tard, on bouge alors
    try{
      var mo = new MutationObserver(function(){
        if (moveWrap()){
          mo.disconnect();
        }
      });
      mo.observe(document.documentElement, {childList:true, subtree:true});
    }catch(_){}
  });
})();
