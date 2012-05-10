
              PagSecuro payment plugin configuration http://pagseguro.uol.com.br

1. Enable "PagSecuro" payment plugin at aMember CP->Setup->Plugins
2. Configure "PagSecuro" payment plugin at aMember CP->Setup->PagSecuro
3. Activate “return URL” at your PagSeguro merchant account.
   To activate Automatic Data Return, 
   select the option Ativar and inform 
   the URL to which PagSeguro will redirect 
   your customers after completion  
   of payment. After that, click Salvar.
   You have to set up {$config.root_url}/plugins/payment/pagseguro/ipn.php as "return URL".