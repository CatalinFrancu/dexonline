  <Lexem id="{$lexem->id}">
    <Timestamp>{$lexem->modDate}</Timestamp>
    <Form>{$lexem->form|escape}</Form>
    {if $lexem->description}
      <Description>{$lexem->description|escape}</Description>
    {/if}
    {assign var="ifs" value=$lexem->loadInflectedForms()}
    {foreach $ifs as $if}
      <InflectedForm>
        <InflectionId>{$if->inflectionId}</InflectionId>
        <Form>{$if->form|escape}</Form>
      </InflectedForm>
    {/foreach}
  </Lexem>

