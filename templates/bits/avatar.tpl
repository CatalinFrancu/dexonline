<img class="avatar"
  {if $user->hasAvatar}
     src="{Config::STATIC_URL}img/user/{$user->id}.jpg?cb={1000000000|rand:9999999999}"
  {else}
     src="{$imgRoot}/avatar_user.png"
  {/if}
     alt="imagine de profil: {$user->nick|escape}"
>
