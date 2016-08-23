<nav class="navbar navbar-default">
  <div class="container">
    <div class="navbar-header navbar-left pull-left">
      {if !$onHomePage}
        <a class="navbar-brand" href="{$wwwRoot}" title="Prima pagină">
          <img id="logo-wide" alt="logo dexonline" src="{$wwwRoot}img/logo/logo-nav-wide.png">
          <img id="logo-narrow" alt="logo dexonline" src="{$wwwRoot}img/logo/logo-nav-narrow.png">
        </a>
      {/if}
    </div>

    <div class="navbar-header navbar-right pull-right">
      <ul class="nav navbar-nav pull-left">
        <li>
          <p class="navbar-btn">
            <a class="btn btn-info" href="{$wwwRoot}doneaza">
              <i class="glyphicon glyphicon-credit-card"></i>
              Donează
            </a>
          </p>
        </li>
      </ul>

      <button type="button" class="navbar-toggle collapsed hamburger-menu" data-toggle="collapse" data-target="#navMenu" aria-expanded="false">
        <span class="sr-only">Navigare</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
    </div>

    <div class="collapse navbar-collapse" id="navMenu">
      <!-- Collect the nav links, forms, and other content for toggling -->
      <ul class="nav navbar-nav">
        <li class="dropdown">
          <a href="#" class="dropdown-toggle about-us" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Despre noi <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a href="http://wiki.dexonline.ro/wiki/Informa%C8%9Bii" target="_blank">Informații</a></li>
            <li><a href="{$wwwRoot}contact">Contact</a></li>
            <li><a href="http://dexonline.blogspot.ro">Blogul nostru</a></li>
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Implică-te <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a href="{$wwwRoot}contribuie">Contribuie cu definiții</a></li>
            <li><a href="{$wwwRoot}top">Topul voluntarilor</a></li>
            <li><a href="http://wiki.dexonline.ro/wiki/Informa%C8%9Bii_pentru_programatori">Informații pentru programatori</a></li>
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Articole și resurse <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a href="http://wiki.dexonline.ro/wiki/Abrevieri" target="_blank">Abrevieri folosite</a></li>
            <li><a href="{$wwwRoot}articole">Articole lingvistice</a></li>
            <li><a href="{$wwwRoot}articol/Ghid_de_exprimare_corect%C4%83">Ghid de exprimare</a></li>
            <li><a href="{$wwwRoot}cuvantul-zilei">Cuvântul zilei</a></li>
            <li><a href="{$wwwRoot}cuvantul-lunii">Cuvântul lunii</a></li>
            <li><a href="{$wwwRoot}cuvinte-aleatoare">Cuvinte aleatoare</a></li>
            <li><a href="{$wwwRoot}scrabble">Scrabble</a></li>
            <li><a href="{$wwwRoot}unelte">Unelte</a></li>
            <li><a href="{$wwwRoot}legaturi">Legături externe</a></li>
          </ul>
        </li>
      </ul>
      {if !$cfg.global.mirror}
        <ul class="nav navbar-nav navbar-right">
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown"
               role="button" aria-haspopup="true" aria-expanded="false">
              <span class="glyphicon glyphicon-user"></span>
              {$nick|escape}
              <span class="caret"></span>
            </a>
            <ul class="dropdown-menu">
              {if $sUser && $sUser->moderator}
                <li><a href="{$wwwRoot}admin">Pagina moderatorului</a></li>
              {/if}
              <li><a href="{$wwwRoot}preferinte">Preferințe</a></li>
              {if $sUser}
                <li><a href="{$wwwRoot}utilizator/{$sUser->nick}">Profilul meu</a></li>
                <li><a href="{$wwwRoot}cuvinte-favorite">Cuvinte favorite</a></li>
                <li><a href="{$wwwRoot}auth/logout">Închide sesiunea</a></li>
              {else}
                <li><a href="{$wwwRoot}auth/login">Autentificare cu OpenID</a></li>
              {/if}
            </ul>
          </li>
        </ul>
      {/if}

    </div>
  </div>
</nav>
