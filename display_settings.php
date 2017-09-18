<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/bulma/0.5.1/css/bulma.css">
<!-- STYLE -->
<style>
  pre {
  white-space: pre-wrap;       /* css-3 */
  white-space: -moz-pre-wrap;  /* Mozilla, since 1999 */
  white-space: -pre-wrap;      /* Opera 4-6 */
  white-space: -o-pre-wrap;    /* Opera 7 */
  word-wrap: break-word;       /* Internet Explorer 5.5+ */
  }
  .pagination_custom {
    clear: both;
    padding: 20px 0;
    position: relative;
    font-size: 11px;
    line-height: 13px;
  }
  
  .pagination_custom span,
  .pagination_custom a {
    display: block;
    float: left;
    margin: 2px 2px 2px 0;
    padding: 6px 9px 5px 9px;
    text-decoration: none;
    width: auto;
    color: #fff;
    background: #555;
  }
  
  .pagination_custom a:hover {
    color: #fff;
    background: #3279BB;
  }
  
  .pagination_custom .current {
    padding: 6px 9px 5px 9px;
    background: #3279BB;
    color: #fff;
  }
  
  .tgl {
    display: none !important;
  }
  
  .tgl,
  .tgl:after,
  .tgl:before,
  .tgl *,
  .tgl *:after,
  .tgl *:before,
  .tgl + .tgl-btn {
    box-sizing: border-box;
  }
  
  .tgl::-moz-selection,
  .tgl:after::-moz-selection,
  .tgl:before::-moz-selection,
  .tgl *::-moz-selection,
  .tgl *:after::-moz-selection,
  .tgl *:before::-moz-selection,
  .tgl + .tgl-btn::-moz-selection {
    background: none;
  }
  
  .tgl::selection,
  .tgl:after::selection,
  .tgl:before::selection,
  .tgl *::selection,
  .tgl *:after::selection,
  .tgl *:before::selection,
  .tgl + .tgl-btn::selection {
    background: none;
  }
  
  .tgl + .tgl-btn {
    outline: 0;
    display: block;
    width: 4em;
    height: 2em;
    position: relative;
    cursor: pointer;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
  }
  
  .tgl + .tgl-btn:after,
  .tgl + .tgl-btn:before {
    position: relative;
    display: block;
    content: "";
    width: 50%;
    height: 100%;
  }
  
  .tgl + .tgl-btn:after {
    left: 0;
  }
  
  .tgl + .tgl-btn:before {
    display: none;
  }
  
  .tgl:checked + .tgl-btn:after {
    left: 50%;
  }
  
  .tgl-flat + .tgl-btn {
    padding: 2px;
    -webkit-transition: all .2s ease;
    transition: all .2s ease;
    background: #fff;
    border: 4px solid #f2f2f2;
    border-radius: 2em;
  }
  
  .tgl-flat + .tgl-btn:after {
    -webkit-transition: all .2s ease;
    transition: all .2s ease;
    background: #f2f2f2;
    content: "";
    border-radius: 1em;
  }
  
  .tgl-flat:checked + .tgl-btn {
    border: 4px solid #7FC6A6;
  }
  
  .tgl-flat:checked + .tgl-btn:after {
    left: 50%;
    background: #7FC6A6;
  }
  /** FINALIZAR **/
  .group {
    width: 400px;
    height: 40px;
    padding: 10px 10px;
    line-height: 40px;
    position: relative;
    z-index: 1;
    margin-left: auto;
    margin-right: auto;
    margin-top: 30px;
    margin-bottom: 50px;
    font-weight: 700;
    text-transform:uppercase;
    font-size: 20px;
}

.group .one {
    float: left;
    line-height: 40px;
    color: white;
    text-decoration: none;
    transition: 1.0s;
}
.group .two {
    float: right;
    text-decoration: none;
    line-height: 40px;
    color: #a6afbf;
   text-align:center;
}
.group .bg {
    background-color: #e67e22;
    height: 60px;
    width: 190px;
    position: absolute;
    top: 0;
    margin-left: -287px;
    z-index: -1;
    transition: 1.0s;
    border-radius: 5px;
    
}

.group span:hover + .bg {
    width: 200px;
    margin-left: -55px;
    background: #27ae60;
}

.group .two > span:hover {
    color: white;
}

.group .two:hover ~ .one {
    color: #a6afbf;
}
.group span {
    padding: 10px 0;
    transition: 1.0s;
}

</style>
<?php
$dados_gerais = $bill->getBasicData();
$config_por_defeito = $bill->getDefaultConfig();
?>
  <div class="wrap">
    <div class="tabs">
      <ul>
        <li class="<?php $bill->isActive('configuracoes'); ?>"><a href="admin.php?page=bill_settings"><?php echo __("Configurações","bill-faturacao"); ?></a></li>
        <li class="<?php $bill->isActive('encomendas'); ?>"><a href="admin.php?page=bill_settings&tab=encomendas"><?php echo __("Encomendas","bill-faturacao"); ?></a></li>
        <li class="<?php $bill->isActive('documento'); ?> <?php $bill->isVisible('documento'); ?>"><a href="admin.php?page=bill_settings&tab=documento"><?php echo __("Ver Documento","bill-faturacao"); ?></a></li>
      </ul>
    </div>
    <?php if( $bill->showConfiguracoes()) { ?>
    <div class="<?php $bill->isVisible('configuracoes'); ?>">
      <div class="columns">
        <div class="column">
          <div class="box">

            <p class="subtitle is-6"><strong><?php echo __("Configurar Token","bill-faturacao"); ?></strong>
              <br/><?php echo __("Deverá logar com os seus dados para obter o token. Deverá ligar a API para o seu utilizador nas permissões do Bill.pt","bill-faturacao"); ?></p>
            <table class="widefat">
              <thead>
                <tr>
                  <th><?php echo __("Email","bill-faturacao"); ?></th>
                  <th><?php echo __("Password","bill-faturacao"); ?></th>
                  <th><?php echo __("Action","bill-faturacao"); ?></th>
                  <th><?php echo __("State","bill-faturacao"); ?></th>
                </tr>
              </thead>
              <tfoot>
                <tr>
                <th><?php echo __("Email","bill-faturacao"); ?></th>
                <th><?php echo __("Password","bill-faturacao"); ?></th>
                <th><?php echo __("Action","bill-faturacao"); ?></th>
                <th><?php echo __("State","bill-faturacao"); ?></th>
                </tr>
              </tfoot>
              <tbody>
                <form method="POST">
                  <tr>
                    <td>
                      <input class="input" id="email" name="email_bill" type="email" value="" required />
                    </td>
                    <td>
                      <input class="input" id="password" name="password" type="password" value="" required />
                    </td>
                    <td>
                      <input type="submit" value="<?php _e(" Get Token ","bill-faturacao"); ?>" class="button-secondary" />
                    </td>
                    <td>
                      <?php echo $bill->isLogged() ? '<span class="tag is-success">Ok</span>' : '<span class="tag is-danger">' . __("Falta","bill-faturacao") . '</span>'; ?></td>
                  </tr>
                </form>
              </tbody>
            </table>
          </div>

          <div class="box">
            <p class="subtitle is-6"><strong><?php echo __("Base de dados","bill-faturacao"); ?></strong>
              <br/><?php echo __("Tenha a certeza que actualizou todos os dados antes de começar a Faturar.","bill-faturacao"); ?></p>
            <table class="widefat">
              <thead>
                <tr>
                  <th><?php echo __("Dados","bill-faturacao"); ?></th>
                  <th><?php echo __("Estado","bill-faturacao"); ?></th>
                  <th><?php echo __("Update","bill-faturacao"); ?></th>
                </tr>
              </thead>
              <tfoot>
                <tr>
                  <th><?php echo __("Dados","bill-faturacao"); ?></th>
                  <th><?php echo __("Estado","bill-faturacao"); ?></th>
                  <th><?php echo __("Update","bill-faturacao"); ?></th>
                </tr>
              </tfoot>
              <tbody>
                <tr>
                  <td><?php echo __("Lojas","bill-faturacao"); ?></td>
                  <td>
                    <?php $bill->vazio($dados_gerais['loja']); ?>
                  </td>
                  <td>
                    <a class="button-secondary" href="admin.php?page=bill_settings&update_config=loja" title="All Attendees"><?php echo __("Update","bill-faturacao","bill-faturacao"); ?></a>
                  </td>
                </tr>
                <tr>
                  <td><?php echo __("Séries","bill-faturacao"); ?></td>
                  <td>
                    <?php $bill->vazio($dados_gerais['serie']); ?>
                  </td>
                  <td>
                    <a class="button-secondary" href="admin.php?page=bill_settings&update_config=serie" title="All Attendees"><?php echo __("Update","bill-faturacao"); ?></a>
                  </td>
                </tr>
                <tr>
                  <td>
                    <?php echo __("Tipos Documento","bill-faturacao"); ?>
                  </td>
                  <td>
                    <?php $bill->vazio($dados_gerais['tipo_documento']); ?>
                  </td>
                  <td>
                    <a class="button-secondary" href="admin.php?page=bill_settings&update_config=tipo_documento" title="All Attendees"><?php echo __("Update","bill-faturacao"); ?></a>
                  </td>
                </tr>
                <tr>
                  <td>
                    <?php echo __("Unidades de Medida","bill-faturacao"); ?>
                  </td>
                  <td>
                    <?php $bill->vazio($dados_gerais['unidade_medida']); ?>
                  </td>
                  <td>
                    <a class="button-secondary" href="admin.php?page=bill_settings&update_config=unidade_medida" title="All Attendees"><?php echo __("Update","bill-faturacao"); ?></a>
                  </td>
                </tr>
                <tr>
                  <td>
                    <?php echo __("Método de Expedição","bill-faturacao"); ?>
                  </td>
                  <td>
                    <?php $bill->vazio($dados_gerais['metodo_entrega']); ?>
                  </td>
                  <td>
                    <a class="button-secondary" href="admin.php?page=bill_settings&update_config=metodo_entrega" title="All Attendees"><?php echo __("Update","bill-faturacao"); ?></a>
                  </td>
                </tr>
                <tr>
                  <td>
                    <?php echo __("Método Pagamento","bill-faturacao"); ?>
                  </td>
                  <td>
                    <?php $bill->vazio($dados_gerais['metodo_pagamento']); ?>
                  </td>
                  <td>
                    <a class="button-secondary" href="admin.php?page=bill_settings&update_config=metodo_pagamento" title="All Attendees"><?php echo __("Update","bill-faturacao"); ?></a>
                  </td>
                </tr>
                <tr>
                  <td>
                    <?php echo __("Imposto","bill-faturacao"); ?>
                  </td>
                  <td>
                    <?php $bill->vazio($dados_gerais['imposto']); ?>
                  </td>
                  <td>
                    <a class="button-secondary" href="admin.php?page=bill_settings&update_config=imposto" title="All Attendees"><?php echo __("Update","bill-faturacao"); ?></a>
                  </td>
                </tr>
                <tr>
                  <td>
                    <?php echo __("Isenção","bill-faturacao"); ?>
                  </td>
                  <td>
                    <?php $bill->vazio($dados_gerais['isencao']); ?>
                  </td>
                  <td>
                    <a class="button-secondary" href="admin.php?page=bill_settings&update_config=isencao" title="All Attendees"><?php echo __("Update","bill-faturacao"); ?></a>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>
        <div class="column">

          <div class="box">
            <p class="subtitle is-6"><strong><?php echo __("Valores por defeito","bill-faturacao"); ?></strong>
              <br/><?php echo __("Poderá configurar alguns valores por defeito para tornar a emissão dos documentos mais simples.","bill-faturacao"); ?></p>
            <p><strong><?php echo __("Código Portes Envio:","bill-faturacao"); ?></strong> <?php echo __("Este deverá ser o código do Bill do Serviço de envio. Se não colocar nenhum será criado um novo automáticamente na primeira fatura.","bill-faturacao"); ?></p>
            <br />
            <form method="POST">
              <input type="hidden" name="update_default_config" value="1">

              <div class="columns">
              <div class="column">
                <div class="field">
                  <label class="label is-danger"><?php echo __("Bill API MODO","bill-faturacao"); ?></label>
                  <div class="control">
                    <div class="select is-fullwidth">
                      <select name="api_mode" id="api_mode">
                         <option value="portugal" <?php selected('portugal', $config_por_defeito->api_mode) ?>>Portugal</option>
                         <option value="world" <?php selected('world', $config_por_defeito->api_mode) ?>>International</option>
                         <option value="dev" <?php selected('dev', $config_por_defeito->api_mode) ?>>Test Server</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>

              <div class="column">
                <div class="field">
                  <label class="label"><?php echo __("Debug (apenas para programadores)","bill-faturacao"); ?></label>
                  <div class="control">
                    <div class="select is-fullwidth">
                    <select name="debug" id="debug">
                    <option value="0" <?php selected(0, $config_por_defeito->debug) ?>>OFF</option>
                    <option value="on" <?php selected("on", $config_por_defeito->debug) ?>>ON</option>
                 </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>

              <div class="columns">
                <div class="column">
                  <div class="field">
                    <label class="label"><?php echo __("Série Documento","bill-faturacao"); ?></label>
                    <div class="control">
                      <div class="select is-fullwidth">
                        <select name="serie" id="serie">
                          <?php $bill->populateSelectConfig('serie', $dados_gerais, $config_por_defeito); ?>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="column">
                  <div class="field">
                    <label class="label"><?php echo __("Loja","bill-faturacao"); ?></label>
                    <div class="control">
                      <div class="select is-fullwidth">
                        <select name="loja" id="loja">
                          <?php $bill->populateSelectConfig('loja', $dados_gerais, $config_por_defeito); ?>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="columns">
                <div class="column">
                  <div class="field">
                    <label class="label"><?php echo __("Código Portes de Envio","bill-faturacao"); ?></label>
                    <div class="control">
                      <div class="is-fullwidth">
                        <?php $codigo_portes = (isset($config_por_defeito->codigo_portes)) ? $config_por_defeito->codigo_portes : ''; ?>
                          <input name="codigo_portes" type="text" class="input" value="<?php echo $codigo_portes ?>">
                      </div>
                    </div>
                  </div>
                </div>

                <div class="column">
                  <div class="field">
                    <label class="label"><?php echo __("Motivo Isenção","bill-faturacao"); ?></label>
                    <div class="control">
                      <div class="select is-fullwidth">
                        <select name="isencao" id="isencao">
                          <?php $bill->populateSelectConfig('isencao', $dados_gerais, $config_por_defeito); ?>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="columns">
                <div class="column">
                  <div class="field">
                    <label class="label"><?php echo __("Unidade Medida","bill-faturacao"); ?></label>
                    <div class="control">
                      <div class="select is-fullwidth">
                        <select name="unidade_medida" id="unidade_medida">
                          <?php $bill->populateSelectConfig('unidade_medida', $dados_gerais, $config_por_defeito); ?>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="column">
                  <div class="field">
                    <label class="label"><?php echo __("Método Pagamento","bill-faturacao"); ?></label>
                    <div class="control">
                      <div class="select is-fullwidth">
                        <select name="metodo_pagamento" id="metodo_pagamento">
                          <?php $bill->populateSelectConfig('metodo_pagamento', $dados_gerais, $config_por_defeito); ?>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="columns">
                <div class="column">
                  <div class="field">
                    <label class="label"><?php echo __("Método Expedicao","bill-faturacao"); ?></label>
                    <div class="control">
                      <div class="select is-fullwidth">
                        <select name="metodo_entrega" id="metodo_entrega">
                          <?php $bill->populateSelectConfig('metodo_entrega', $dados_gerais, $config_por_defeito); ?>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="column">
                  <div class="field">
                    <label class="label"><?php echo __("Envio por e-mail","bill-faturacao"); ?></label>
                    <div class="control">
                      <label class="checkbox">
                        <input <?php $bill->isChecked( 'envio_email', $config_por_defeito) ?> type="checkbox" name="envio_email" value="1"> <?php echo __("Automático para e-mail do cliente","bill-faturacao"); ?></a>
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <div class="columns">
                <div class="column">
                  <div class="field">
                    <div class="control">
                    </div>
                  </div>
                </div>

                <div class="column">
                  <div class="field">
                    <div class="control">
                      <input type="submit" value="<?php echo __(" Update ","bill-faturacao"); ?>" class="button-secondary" />
                    </div>
                  </div>
                </div>
              </div>
            </form>
          </div>

        </div>
      </div>

    </div>
    <?php } ?>
    <?php if($bill->showEncomendas()){ ?> 
    <div class="<?php $bill->isVisible('encomendas'); ?>">
      <div class="box">
        <table class="widefat">
          <thead>
            <tr>
              <th><?php echo __("Order","bill-faturacao"); ?></th>
              <th><?php echo __("Nome","bill-faturacao") ?></th>
              <th><?php echo __("Total","bill-faturacao") ?></th>
              <th><?php echo __("Orçamento","bill-faturacao") ?></th>
              <th><?php echo __("Encomenda","bill-faturacao") ?></th>
              <th><?php echo __("Guia Transporte & Fatura","bill-faturacao") ?></th>
              <th><?php echo __("Fatura","bill-faturacao") ?></th>
              <th><?php echo __("Fatura Recibo","bill-faturacao") ?></th>
              <th><?php echo __("Fatura Simplificada","bill-faturacao") ?></th>
              <th><?php echo __("Recibo","bill-faturacao") ?></th>
              <th><?php echo __("Date","bill-faturacao") ?></th>
            </tr>
          </thead>
          <tfoot>
            <tr>
            <th><?php echo __("Order","bill-faturacao"); ?></th>
            <th><?php echo __("Nome","bill-faturacao") ?></th>
            <th><?php echo __("Total","bill-faturacao") ?></th>
            <th><?php echo __("Orçamento","bill-faturacao") ?></th>
            <th><?php echo __("Encomenda","bill-faturacao") ?></th>
            <th><?php echo __("Guia Transporte & Fatura","bill-faturacao") ?></th>
            <th><?php echo __("Fatura","bill-faturacao") ?></th>
            <th><?php echo __("Fatura Recibo","bill-faturacao") ?></th>
            <th><?php echo __("Fatura Simplificada","bill-faturacao") ?></th>
            <th><?php echo __("Recibo","bill-faturacao") ?></th>
            <th><?php echo __("Date","bill-faturacao") ?></th>
            </tr>
          </tfoot>
          <tbody>
            <?php $max_page = $bill->getAllOrders(); ?>
          </tbody>
        </table>
        <?php if($max_page > 0){
    pagination($max_page);
} ?>
      </div>
    </div>
    <?php } ?>

    <div class="<?php $bill->isVisible('documento'); ?>">
      <div class="columns">
        <div class="column">
          <?php $bill->printDocument(); ?>
        </div>
      </div>
    </div>
   
    <hr>
    
  </div>
  <?php  $bill->printDebugFromMemory(); ?>