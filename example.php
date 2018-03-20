<?php
include 'cloudflare.php';

$html = <<<HTML
var s,t,o,p,b,r,e,a,k,i,n,g,f, SdgCLeG={"q":!+[]+!![]+!+[]+!![]+!![]+!![]};
        t = document.createElement('div');
        t.innerHTML="<a href='/'>x</a>";
        t = t.firstChild.href;r = t.match(/https?:\/\//)[0];
        t = t.substr(r.length); t = t.substr(0,t.length-1);
        a = document.getElementById('jschl-answer');
        f = document.getElementById('challenge-form');
        ;SdgCLeG.q+=+((!+[]+!![]+!![]+[])+(+[]));SdgCLeG.q*=+((!+[]+!+[]+!![]+!+[]+[])+(+!![]));SdgCLeG.q-=+((!+[]+!![]+!![]+[])+(+!![]));SdgCLeG.q*=+((+!![]+[])+(!+[]+!![]+!![]+!![]+!![]+!![]+!![]+!![]));SdgCLeG.q*=+((!+[]+!![]+!![]+!![]+[])+(+[]));a.value = parseInt(SdgCLeG.q, 10) + t.length; '; 121'
        f.submit();
      }, 4000);
    }, false); 
  <form id="challenge-form" action="/cdn-cgi/l/chk_jschl" method="get">
    <input type="hidden" name="jschl_vc" value="1a79a4d60de6718e8e5b326e338ae533"/>
    <input type="hidden" name="pass" value="1487112786.013-dEMzfIsOva"/>
    <input type="hidden" id="jschl-answer" name="jschl_answer"/>
  </form>
HTML;

$cf = new CloudflareSolver('example.com', $html);
echo 'Waiting for ' . $cf->getTimeout() / 1000 . ' seconds...' . PHP_EOL;
usleep($cf->getTimeout() * 1000);

// TODO: Go to the URL
echo $cf->getSolvedUrl();