<?php
use EpicBit\BillPhpSdk\Api as API;

class BillPT
{
    protected $api;
    protected $logged;
    protected $error;
    protected $basic_data     = null;
    protected $default_config = null;
    protected $url;

    public function __construct($mode)
    {
        $default = $this->getDefaultConfig();

        $valid = ['standard', 'dev', 'world', 'portugal'];
        if (isset($default->api_mode) && in_array($default->api_mode, $valid)) {
            $mode = $default->api_mode;

            switch ($mode) {
                case 'standard':
                case 'portugal':
                    $this->url = "https://app.bill.pt";
                    break;
                case 'world':
                    $this->url = "https://int.bill.pt";
                    break;
                case 'dev':
                    $this->url = "https://dev.bill.pt";
                    break;
                default:
                    $this->url = "https://app.bill.pt";
                    break;
            }
        }

        $this->api = new API($mode);
        if ($this->isDebugOn()) {
            $this->api->setLog(true, 'memory');
        }
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function isDebugOn()
    {
        $default = $this->getDefaultConfig();

        if (isset($default->debug) && $default->debug === "on") {
            return true;
        }
        return false;
    }

    public function varDumpToString($var)
    {
        ob_start();
        $var = $this->api->isJson($var) ? json_decode($var) : $var;
        var_dump($var);
        $result = ob_get_clean();
        return $result;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getApi()
    {
        return $this->api;
    }

    public function isLogged()
    {
        return $this->logged;
    }

    public function addError($error)
    {
        if (is_array($error)) {
            foreach ($error as $e) {
                $this->error[] = $e;
            }

            return true;
        }

        $this->error[] = $error;
    }

    public function cleanError()
    {
        $this->error = [];
    }

    public function printDebugFromMemory()
    {
        if ($this->isDebugOn()) {
            $logs = $this->api->getLogFromMemory();
            if (isset($logs[0])) {
                foreach ($logs as $log) {
                    $params_dump = $this->varDumpToString($log['params']);
                    $result_dump = $this->varDumpToString($log['result']);
                    echo '<div class="container"><div class="box">
          <div class="control">
            <div class="tags has-addons">
              <span class="tag is-warning">' . $log['method'] . '</span>
              <span class="tag is-dark">' . $log['url'] . '</span>
            </div>
          </div>
          <hr>
          <div class="control">
            <div class="tags has-addons">
              <span class="tag is-info">Parameters</span>
            </div>
            <div class="tags has-addons">
              <span class="tag is-info">Time</span>
              <span class="tag is-black">' . $log['response_time']['total'] . '</span>
            </div>
          </div>
          <pre>' . $params_dump . '</pre>
          <hr>
          <div class="control">
            <div class="tags has-addons">
              <span class="tag is-success">Response</span>
            </div>
          </div>
          <pre>' . $result_dump . '</pre>
        </div></div><hr>';
                }
            }
        }
    }

    public function printErrors()
    {
        if (isset($this->error[0])) {
            echo '<div class="notification is-danger"><strong>Errors:</strong>';
            foreach ($this->error as $e) {
                echo $e . '<br/>';
            }
            echo '</div>';
        }

        $this->cleanError();
    }

    public function preDump($data)
    {
        echo '<div class="notification"><strong>Dump:</strong><pre>';
        var_dump($data);
        echo '</pre></div>';
    }

    private function getUnidadeMedidaID()
    {
        $default = $this->getBasicData();
        foreach ($default['unidade_medida'] as $unidade) {
            $unidade = json_decode($unidade->value);
            if ($unidade->simbolo == "UN") {
                return $unidade->id;
            }
        }

        return 0;
    }

    private function getImpostoID()
    {
        $default = $this->getBasicData();
        foreach ($default['imposto'] as $imposto) {
            $imposto = json_decode($unidade->value);
            return $imposto->id;
        }

        return 0;
    }

    private function getToken()
    {
        global $wpdb;
        return $wpdb->get_row('SELECT * FROM bill_config WHERE config = "token"');
    }

    private function getItemFromDB($codigo)
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM bill_produtos WHERE codigo = %s", $codigo)
        );
    }

    public function updateItemDB($produto)
    {
        global $wpdb;
        $wpdb->delete('bill_produtos',
            array('item_id' => $produto->id)
        );
        $wpdb->delete('bill_produtos',
            array('codigo' => $produto->codigo)
        );

        $wpdb->insert('bill_produtos', [
            'codigo'  => $produto->codigo,
            'item_id' => $produto->id,
        ], ['%s', '%d']);
    }

    public function vazio($data)
    {
        echo count($data) > 0 ? '<span class="tag is-success">Ok</span>' : '<span class="tag is-danger">' . __("Falta", "bill-faturacao") . '</span>';
    }

    public function showConfiguracoes()
    {
        if (isset($_GET['tab']) && ($_GET['tab'] == 'documento' || $_GET['tab'] == 'encomendas')) {
            return;
        }
        return true;
    }

    public function showEncomendas()
    {
        if (isset($_GET['tab']) && $_GET['tab'] == 'encomendas') {
            return true;
        }
        return false;
    }

    public function login()
    {
        $api = $this->api;
        if (isset($_POST['email_bill']) && isset($_POST['password'])) {

            $user = $api->getToken([
                'email'    => sanitize_email($_POST['email_bill']),
                'password' => $_POST['password'],
            ]);

            if (isset($user->api_token)) {
                $this->updateToken($user);
                $this->api->setToken($token);
                $this->logged = true;
                return true;
            } else {
                if (isset($user->error)) {
                    $this->addError($user->error);
                } else {
                    $this->addError(var_dump((string) $user));
                }

                $this->printErrors();
                $this->logged = false;
                return $this->logged;
            }
        }

        $token = $this->getToken();
        $token = (isset($token->value)) ? $token->value : "";

        if ($token == "") {
            return false;
        }

        $this->api->setToken($token);

        $this->logged = true;
        return $this->logged;
    }

    public function validateToken()
    {
        $token = $this->getToken();
        $token = (isset($token->value)) ? $token->value : "";
        $api   = $this->api;
        $api->setToken($token);

        if (!$api->validToken()) {
            global $wpdb;
            $wpdb->delete('bill_config',
                array('config' => 'token')
            );

            $_GET['tab'] = "configuracoes";
            $this->addError(__('Deverá configurar o seu token fazendo login com os seus dados.', "bill-faturacao"));
            $this->printErrors();

            $this->logged = false;
            return $this->logged;
        }

        return true;
    }

    public function updateToken($user)
    {
        global $wpdb;

        $wpdb->delete('bill_config', array('config' => 'token'));

        $wpdb->insert('bill_config', [
            'config' => 'token',
            'value'  => $user->api_token,
        ], ['%s', '%s']);

    }

    public function getBasicData()
    {
        if (!is_null($this->basic_data)) {
            return $this->basic_data;
        }

        global $wpdb;

        $dados_gerais['loja']             = $wpdb->get_results('SELECT * FROM bill_config WHERE config = "loja"');
        $dados_gerais['tipo_documento']   = $wpdb->get_results('SELECT * FROM bill_config WHERE config = "tipo_documento"');
        $dados_gerais['unidade_medida']   = $wpdb->get_results('SELECT * FROM bill_config WHERE config = "unidade_medida"');
        $dados_gerais['metodo_entrega']   = $wpdb->get_results('SELECT * FROM bill_config WHERE config = "metodo_entrega"');
        $dados_gerais['serie']            = $wpdb->get_results('SELECT * FROM bill_config WHERE config = "serie"');
        $dados_gerais['metodo_pagamento'] = $wpdb->get_results('SELECT * FROM bill_config WHERE config = "metodo_pagamento"');
        $dados_gerais['imposto']          = $wpdb->get_results('SELECT * FROM bill_config WHERE config = "imposto"');
        $dados_gerais['isencao']          = $wpdb->get_results('SELECT * FROM bill_config WHERE config = "isencao"');

        $this->basic_data = $dados_gerais;
        return $dados_gerais;
    }

    public function getDefaultConfig()
    {
        if (!is_null($this->default_config)) {
            return $this->default_config;
        }
        global $wpdb;

        $default_config = $wpdb->get_row('SELECT * FROM bill_config WHERE config = "default_config"');

        if (isset($default_config->value) && strlen($default_config->value) > 3) {
            $default_config = json_decode($default_config->value);
        }

        $this->default_config = $default_config;
        return $default_config;
    }

    public function updateDefaultConfig()
    {
        if (isset($_POST['update_default_config'])) {
            global $wpdb;
            $wpdb->delete('bill_config', array('config' => 'default_config'));

            if (isset($_POST['loja'])) {
                $config['loja'] = (int) $_POST['loja'];
            }

            if (isset($_POST['serie'])) {
                $config['serie'] = (int) $_POST['serie'];
            }

            if (isset($_POST['imposto'])) {
                $config['imposto'] = (int) $_POST['imposto'];
            }

            if (isset($_POST['isencao'])) {
                $config['isencao'] = sanitize_text_field(substr($_POST['isencao'], 0, 3));
            }

            if (isset($_POST['unidade_medida'])) {
                $config['unidade_medida'] = (int) $_POST['unidade_medida'];
            }

            if (isset($_POST['metodo_pagamento'])) {
                $config['metodo_pagamento'] = (int) $_POST['metodo_pagamento'];
            }

            if (isset($_POST['metodo_entrega'])) {
                $config['metodo_entrega'] = (int) $_POST['metodo_entrega'];
            }

            if (isset($_POST['envio_email'])) {
                $config['envio_email'] = (int) $_POST['envio_email'];
            }

            if (isset($_POST['codigo_portes'])) {
                $config['codigo_portes'] = sanitize_text_field(strip_tags($_POST['codigo_portes']));
            }

            if (isset($_POST['api_mode'])) {
                $valid              = ['standard', 'dev', 'world', 'portugal'];
                $config['api_mode'] = in_array($_POST['api_mode'], $valid) ? $_POST['api_mode'] : 'standard';
            }

            if (isset($_POST['debug'])) {
                $_POST['debug']  = ($_POST['debug'] == "on") ? "on" : 0;
                $config['debug'] = $_POST['debug'];
            }

            $wpdb->insert('bill_config', [
                'config' => 'default_config', 'value' => json_encode($config)], ['%s', '%s']);
        }

        global $wpdb;
        $default_config = $wpdb->get_row('SELECT * FROM bill_config WHERE config = "default_config"');

        if (isset($default_config->value) && strlen($default_config->value) > 3) {
            $default_config = json_decode($default_config->value);
        }

        $this->default_config = $default_config;
    }

    public function populateSelectConfig($config, $data, $default)
    {
        echo '<option></option>';
        foreach ($data[$config] as $linha) {
            $linha    = json_decode($linha->value);
            $value    = isset($linha->codigo) ? $linha->codigo : $linha->id;
            $nome     = isset($linha->motivo) ? $linha->codigo . ' ' . $linha->motivo : $linha->nome;
            $selected = (isset($default->{$config}) && $default->{$config} == $value) ? 'selected="selected"' : '';
            echo '<option ' . $selected . ' value="' . $value . '">' . $nome . '</option>';
        }
    }

    public function isChecked($config, $default)
    {
        echo (isset($default->{$config}) && $default->{$config} == 1) ? 'checked="checked"' : '';
    }

    public function updateConfig()
    {
        if (!isset($_GET['update_config'])) {
            return;
        }

        if (!$this->validateToken()) {
            return;
        }

        global $wpdb;
        $api    = $this->api;
        $config = sanitize_text_field($_GET['update_config']);

        switch ($config) {
            case 'loja':
                $wpdb->delete('bill_config', array('config' => 'loja'));

                $linhas = $api->getWarehouses();
                foreach ($linhas as $linha) {
                    $wpdb->insert('bill_config', [
                        'config' => 'loja',
                        'value'  => json_encode($linha),
                    ], ['%s', '%s']);
                }
                break;
            case 'tipo_documento':
                $valido = ['NENC', 'ORC', 'GT', 'FT', 'FR', 'RC', 'FS'];
                $wpdb->delete('bill_config', array('config' => 'tipo_documento'));

                $linhas = $api->getDocumentAllTypes();
                foreach ($linhas as $linha) {
                    if (in_array($linha->tipificacao, $valido)) {
                        $wpdb->insert('bill_config', [
                            'config' => 'tipo_documento',
                            'value'  => json_encode($linha),
                        ], ['%s', '%s']);
                    }
                }
                break;
            case 'unidade_medida':
                $wpdb->delete('bill_config', array('config' => 'unidade_medida'));
                $linhas = $api->getMeasurementUnits();
                foreach ($linhas as $linha) {
                    $wpdb->insert('bill_config', [
                        'config' => 'unidade_medida',
                        'value'  => json_encode($linha),
                    ], ['%s', '%s']);
                }
                break;
            case 'metodo_entrega':
                $wpdb->delete('bill_config', array('config' => 'metodo_entrega'));
                $linhas = $api->getDeliveryMethods();
                foreach ($linhas as $linha) {
                    $wpdb->insert('bill_config', [
                        'config' => 'metodo_entrega',
                        'value'  => json_encode($linha),
                    ], ['%s', '%s']);
                }
                break;
            case 'serie':
                $wpdb->delete('bill_config', array('config' => 'serie'));
                $linhas = $api->getDocumentSets();
                foreach ($linhas as $linha) {
                    $wpdb->insert('bill_config', [
                        'config' => 'serie',
                        'value'  => json_encode($linha),
                    ], ['%s', '%s']);
                }
                break;

            case 'metodo_pagamento':
                $wpdb->delete('bill_config', array('config' => 'metodo_pagamento'));
                $linhas = $api->getPaymentMethods();
                foreach ($linhas as $linha) {
                    $wpdb->insert('bill_config', [
                        'config' => 'metodo_pagamento',
                        'value'  => json_encode($linha),
                    ], ['%s', '%s']);
                }
                break;
            case 'imposto':
                $wpdb->delete('bill_config', array('config' => 'imposto'));
                $linhas = $api->getTaxs();
                foreach ($linhas as $linha) {
                    $wpdb->insert('bill_config', [
                        'config' => 'imposto',
                        'value'  => json_encode($linha),
                    ], ['%s', '%s']);
                }
                break;

            case 'isencao':
                $wpdb->delete('bill_config', array('config' => 'isencao'));
                $linhas = $api->getTaxExemptions();
                foreach ($linhas as $linha) {
                    $wpdb->insert('bill_config', [
                        'config' => 'isencao',
                        'value'  => json_encode($linha),
                    ], ['%s', '%s']);
                }
                break;
        }

    }

    public function isValidNif($nif)
    {

        $valid_first_digits = array(1, 2, 3, 5, 6, 8);

        //Verificar se e um numero e se e' composto exactamente por 9 digitos
        if (!is_numeric($nif) || strlen($nif) != 9) {
            return false;
        }

        $narray = str_split($nif);

        //verificar se o primeiro digito e' valido. O primeiro digito indica o tipo de contribuinte.
        if (!in_array($narray[0], $valid_first_digits)) {
            return false;
        }

        $checkbit = $narray[0] * 9;

        for ($i = 2; $i <= 8; $i++) {
            $checkbit += $nif[$i - 1] * (10 - $i);
        }

        $checkbit = 11 - ($checkbit % 11);

        if ($checkbit >= 10) {
            $checkbit = 0;
        }

        if ($nif[8] == $checkbit) {
            return true;
        }

        return false;
    }

    public function isActive($tab)
    {
        if (!isset($_GET['tab']) && $tab == 'configuracoes') {
            echo 'is-active';
        }

        if (isset($_GET['tab']) && $tab == $_GET['tab']) {
            echo 'is-active';
        }
    }

    public function isVisible($tab)
    {
        if (!isset($_GET['tab']) && $tab == 'configuracoes') {
            return;
        }

        if (isset($_GET['tab']) && $tab == $_GET['tab']) {
            return;
        }

        echo 'is-hidden';
    }

    public function documentState($order_id, $already_done = false, $tipo_documento, $meta)
    {
        if ($already_done) {
            switch ($tipo_documento) {
                case 'orcamento':
                    if (isset($meta['_orcamento_id'])) {
                        return '<a href="admin.php?page=bill_settings&tab=documento&order=' . $order_id . '&doc=' . $tipo_documento . '"><span class="tag is-success">' . __('Orçamento', "bill-faturacao") . '</span></a>';
                    }
                    return;
                    break;
                case 'encomenda':
                    if (isset($meta['_encomenda_id'])) {
                        return '<a href="admin.php?page=bill_settings&tab=documento&order=' . $order_id . '&doc=' . $tipo_documento . '"><span class="tag is-success">' . __('Encomenda', "bill-faturacao") . '</span></a>';
                    }
                    return;
                    break;
                case 'guia_e_fatura':
                    if (isset($meta['_guia_id'])) {
                        return '<a href="admin.php?page=bill_settings&tab=documento&order=' . $order_id . '&doc=guia_e_fatura"><span class="tag is-success">' . __('Guia & Fatura', "bill-faturacao") . '</span></a>';
                    }
                    return;
                    break;
                case 'fatura':
                    if (isset($meta['_fatura_id'])) {
                        return '<a href="admin.php?page=bill_settings&tab=documento&order=' . $order_id . '&doc=' . $tipo_documento . '"><span class="tag is-success">' . __('Fatura', "bill-faturacao") . '</span></a>';
                    }
                    return;
                    break;
                case 'fatura_simplificada':
                    if (isset($meta['_fatura_simplificada_id'])) {
                        return '<a href="admin.php?page=bill_settings&tab=documento&order=' . $order_id . '&doc=' . $tipo_documento . '"><span class="tag is-success">' . __('Fatura Simplificada', "bill-faturacao") . '</span></a>';
                    }
                    return;
                    break;
                case 'fatura_recibo':
                    if (isset($meta['_fatura_recibo_id'])) {
                        return '<a href="admin.php?page=bill_settings&tab=documento&order=' . $order_id . '&doc=' . $tipo_documento . '"><span class="tag is-success">' . __('Fatura Recibo', "bill-faturacao") . '</span></a>';
                    }
                    return;
                    break;
                case 'recibo':
                    if (isset($meta['_recibo_id'])) {
                        return '<a href="admin.php?page=bill_settings&tab=documento&order=' . $order_id . '&doc=' . $tipo_documento . '"><span class="tag is-success">' . __('Recibo', "bill-faturacao") . '</span></a>';
                    }
                    return;
                    break;
                default:
                    # code...
                    break;
            }
        }

        if ($tipo_documento == "recibo") {
            if (!isset($meta['_fatura_id']) || !isset($meta['_fatura_terminado']) || !$meta['_fatura_terminado']) {
                return;
            }
        }

        return '<a href="admin.php?page=bill_settings&tab=encomendas&order=' . $order_id . '&doc=' . $tipo_documento . '"><span class="tag is-info">' . __('Não emitido', "bill-faturacao") . '</span></a>';
    }

    public function getCompanyName($meta)
    {
        $name = $meta["_billing_first_name"] . ' ' . $meta["_billing_last_name"];

        $name = isset($meta['_shipping_company']) && strlen($meta['_shipping_company']) > 2 ? $meta['_shipping_company'] : $name;

        $name = isset($meta['_billing_company']) && strlen($meta['_billing_company']) > 2 ? $meta['_billing_company'] : $name;

        echo $name;
    }

    public function printDocumentInfo($meta)
    {
        $dados_gerais       = $this->getBasicData();
        $config_por_defeito = $this->getDefaultConfig();

        echo '<div class="column">';?>
  <div class="notification">
  <input type="hidden" name="criar_documento" value="<?php echo (int) $_GET['order'] ?>">
  <div class="columns">
      <div class="column">
        <div class="field">
          <label class="label"><?php echo __('Tipo Documento', "bill-faturacao"); ?></label>
          <div class="control">
            <div class="is-fullwidth">
              <?php switch (sanitize_text_field($_GET['doc'])) {
            case 'orcamento':
                $title          = __("Orçamento", "bill-faturacao");
                $description    = __("Um Orçamento é um documento que informa o cliente da melhor cotação possível para os respectivos serviços e produtos pretendidos e servirá como documento formal para assinatura de contrato ou emissão de Factura.", "bill-faturacao");
                $tipo_documento = $_GET['doc'];
                break;
            case 'encomenda':
                $title          = __("Encomenda", "bill-faturacao");
                $description    = __("As Notas de Encomenda (também conhecidas como purchase orders, ordens de compra ou pedidos de compra) são um documento que pode emitir, enviar ao seu cliente e que formalizam a vontade de adquirir artigos ou serviços específicos.", "bill-faturacao");
                $tipo_documento = $_GET['doc'];
                break;
            case 'guia_e_fatura':
                $title          = __("Guia de Transporte e Fatura", "bill-faturacao");
                $description    = __("A guia de transporte tem um efeito logístico e é o documento legal emitido para acompanhar bens, em território nacional. Será também emitida uma Fatura sendo que está estara directamente relacionada com a guia.", "bill-faturacao");
                $tipo_documento = $_GET['doc'];
                break;
            case 'fatura':
                $title          = __("Factura", "bill-faturacao");
                $description    = __("A factura é um documento de valor contabilístico que atesta uma transacção comercial entre duas pessoas ou empresas. Deve conter informação sobre o produto ou serviço prestado, bem como a quantidade e o valor desta transação.", "bill-faturacao");
                $tipo_documento = $_GET['doc'];
                break;
            case 'fatura_recibo':
                $title          = __("Factura Recibo", "bill-faturacao");
                $description    = __("A factura recibo é um documento que serve de fatura e comprovativo de pagamento. Tenha em atenção que nunca deverá emitir uma Fatura Recibo de uma encomenda que não se encontra paga. Pois a factura recibo funciona como comprovativo de pagamento.", "bill-faturacao");
                $tipo_documento = $_GET['doc'];
                break;
            case 'fatura_simplificada':
                $title          = __("Factura Simplificada", "bill-faturacao");
                $description    = __("As faturas simplificadas diferem de uma fatura normal por se tratarem de um documento de venda auto-pago, ou seja, assim que o documento é emitido, este fica automaticamente liquidado.", "bill-faturacao");
                $tipo_documento = $_GET['doc'];
                break;
            case 'recibo':
                $title          = __("Recibo", "bill-faturacao");
                $description    = __("O recibo é um comprovativo de pagamento.", "bill-faturacao");
                $tipo_documento = $_GET['doc'];
                break;
        }?>
              <input type="hidden" value="<?php echo $tipo_documento ?>" name="tipo_documento" />
              <h3><?php echo $title; ?></h3>
              <p><?php echo $description; ?></p>
            </div>
          </div>
        </div>
      </div>
   </div>
   <div class="columns">
      <div class="column">
        <div class="field">
          <label class="label"><?php echo __("Data", "bill-faturacao"); ?></label>
          <div class="control">
            <div class="is-fullwidth">
            <input class="input" type="datetime-local" name="data" value="<?php echo strftime('%Y-%m-%dT%H:%M', time()) ?>">
            </div>
          </div>
        </div>
      </div>
      <div class="column">
        <div class="field">
          <label class="label"><?php echo __("Prazo Vencimento", "bill-faturacao") ?></label>
          <div class="control">
            <div class="is-fullwidth">
            <input class="input" type="datetime-local" name="prazo_vencimento">
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="columns">

      <div class="column">
        <div class="field">
          <label class="label"><?php echo __("Loja", "bill-faturacao"); ?></label>
          <div class="control">
            <div class="select is-fullwidth">
              <select name="loja_id" id="loja_id">
                <?php $this->populateSelectConfig('loja', $dados_gerais, $config_por_defeito);?>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>


    <div class="columns">
      <div class="column">
        <div class="field">
          <label class="label"><?php echo __("Série Documento", "bill-faturacao"); ?></label>
          <div class="control">
            <div class="select is-fullwidth">
              <select name="serie_id" id="serie_id">
                <?php $this->populateSelectConfig('serie', $dados_gerais, $config_por_defeito);?>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="column">
        <div class="field">
          <label class="label"><?php echo __("Método Pagamento", "bill-faturacao"); ?></label>
          <div class="control">
            <div class="select is-fullwidth">
              <select name="metodo_pagamento_id" id="metodo_pagamento_id">
                <?php $this->populateSelectConfig('metodo_pagamento', $dados_gerais, $config_por_defeito);?>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="columns">
      <div class="column">
        <div class="field">
          <label class="label"><?php echo __("Método Expedicao", "bill-faturacao"); ?></label>
          <div class="control">
            <div class="select is-fullwidth">
              <select name="metodo_expedicao_id" id="metodo_expedicao_id">
                <?php $this->populateSelectConfig('metodo_entrega', $dados_gerais, $config_por_defeito);?>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="column">
        <div class="field">
          <label class="label"><?php echo __("Envio por e-mail", "bill-faturacao"); ?></label>
          <div class="control">
            <label class="checkbox">
              <input <?php $this->isChecked('envio_email', $config_por_defeito)?> type="checkbox" name="envio_email" value="1"> <?php echo __("Automático para e-mail do cliente", "bill-faturacao"); ?></a>
            </label>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php echo '</div>';
    }

    public function printUserCompanyInfo($meta)
    {
        $nif    = isset($meta['My VAT Number section']) ? $meta['My VAT Number section'] : '';
        $codigo = $this->getContatoCodigo($nif, $meta['_billing_email']);

        echo '<div class="column">'; ?>
    <div class="notification">
      <div class="columns">
        <div class="column">
          <div class="field">
            <label class="label"><?php echo __("Nome Empresa / Cliente", "bill-faturacao"); ?></label>
            <div class="control">
              <div class="is-fullwidth">
                <input class="input" type="text" name="contato[nome]" value="<?php $this->getCompanyName($meta);?>">
              </div>
            </div>
          </div>
        </div>
        <div class="column">
          <div class="field">
            <label class="label"><?php echo __("NIF", "bill-faturacao"); ?></label>
            <div class="control">
              <div class="is-fullwidth">
                <input class="input" type="text" name="contato[nif]" value="<?php echo $nif; ?>">
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="columns">
        <div class="column">
          <div class="field">
            <label class="label"><?php echo __("E-mail", "bill-faturacao"); ?></label>
            <div class="control">
              <div class="is-fullwidth">
                <input class="input" type="text" name="contato[email]" value="<?php echo $meta['_billing_email']; ?>">
              </div>
            </div>
          </div>
        </div>
        <div class="column">
          <div class="field">
            <label class="label"><?php echo __("Código no Bill (se já existir)", "bill-faturacao"); ?></label>
            <div class="control">
              <div class="is-fullwidth">
                <input class="input" type="text" name="contato[codigo]" value="<?php echo $codigo; ?>">
              </div>
            </div>
          </div>
        </div>
      </div>

          <div class="field">
            <label class="label"><?php echo __("Morada", "bill-faturacao"); ?></label>
            <div class="control">
              <div class="is-fullwidth">
                <textarea class="textarea" name="contato[morada]" cols="30" rows="5"><?php echo trim($meta['_billing_address_1'] . ' ' . $meta['_billing_address_2']); ?></textarea>
              </div>
            </div>
          </div>

          <div class="columns">
            <div class="column is-4">
              <div class="field">
                <label class="label"><?php echo __("Pais", "bill-faturacao"); ?></label>
                <div class="control">
                  <div class="select is-fullwidth">
                  <select name="contato[pais]">
                  <?php
foreach ($this->api->getCountriesList() as $code => $name) {
            echo '<option value="' . $code . '"   ' . selected($code, $meta['_billing_country'], false) . '>' . $name . '</option>';
        }?>
                  </select>
                  </div>
                </div>
              </div>
            </div>

            <div class="column is-4">
              <div class="field">
                <label class="label"><?php echo __("Código Postal", "bill-faturacao"); ?></label>
                <div class="control">
                  <div class="is-fullwidth">
                    <input class="input" type="text" name="contato[codigo_postal]" value="<?php echo $meta['_billing_postcode']; ?>">
                  </div>
                </div>
              </div>
            </div>

            <div class="column is-4">
              <div class="field">
                <label class="label"><?php echo __("Cidade", "bill-faturacao"); ?></label>
                <div class="control">
                  <div class="is-fullwidth">
                    <input class="input" type="text" name="contato[cidade]" value="<?php echo $meta['_billing_city']; ?>">
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

    <?php echo '</div>';
    }

    public function printDeliveryAddressTable($meta, $obrigatorio)
    {
        echo '<div class="column">'; ?>
      <div class="notification">
      <?php if (!$obrigatorio) {?>
      <div class="is-pulled-right">
        <strong><?php echo __("Utilizar", "bill-faturacao"); ?></strong>
        <input class="tgl tgl-flat" id="valores_entrega" type="checkbox" <?php echo $obrigatorio ? "checked" : ""; ?> data-mostra="entrega" />
        <label class="tgl-btn" for="valores_entrega"></label>
        </div>
        <?php }?>
      <strong><?php echo __("Morada Entrega", "bill-faturacao"); ?></strong>
        <br/><?php echo __("Dados da entrega", "bill-faturacao"); ?>
    </div>
      <div class="notification moradas" <?php echo $obrigatorio ? "" : "style='display:none'"; ?>>
        <div class="field">
          <label class="label"><?php echo __("Morada", "bill-faturacao"); ?></label>
          <div class="control">
            <div class="is-fullwidth">
              <textarea class="textarea" <?php echo $obrigatorio ? "required" : ""; ?>  <?php echo $obrigatorio ? "name='descarga_morada'" : ""; ?> data-name="descarga_morada" cols="30" rows="5"><?php echo trim($meta['_shipping_address_1'] . ' ' . $meta['_shipping_address_2']); ?></textarea>
            </div>
          </div>
        </div>

        <div class="columns">
          <div class="column is-4">
            <div class="field">
              <label class="label"><?php echo __("Pais", "bill-faturacao"); ?></label>
              <div class="control">
              <div class="select is-fullwidth">
              <select <?php echo $obrigatorio ? "required" : ""; ?>  <?php echo $obrigatorio ? "name='descarga_pais'" : ""; ?> data-name="descarga_pais">
              <?php
foreach ($this->api->getCountriesList() as $code => $name) {
            echo '<option value="' . $code . '"   ' . selected($code, $meta['_billing_country'], false) . '>' . $name . '</option>';
        }?>
              </select>
              </div>
            </div>
          </div>
          </div>

          <div class="column is-4">
            <div class="field">
              <label class="label"><?php echo __("Código Postal", "bill-faturacao"); ?></label>
              <div class="control">
                <div class="is-fullwidth">
                  <input class="input" <?php echo $obrigatorio ? "required" : ""; ?>  <?php echo $obrigatorio ? "name='descarga_codigo_postal'" : ""; ?> type="text" data-name="descarga_codigo_postal" value="<?php echo $meta['_shipping_postcode']; ?>">
                </div>
              </div>
            </div>
          </div>

          <div class="column is-4">
            <div class="field">
              <label class="label"><?php echo __("Cidade", "bill-faturacao"); ?></label>
              <div class="control">
                <div class="is-fullwidth">
                  <input class="input" <?php echo $obrigatorio ? "required" : ""; ?>  <?php echo $obrigatorio ? "name='descarga_cidade'" : ""; ?> type="text" data-name="descarga_cidade" value="<?php echo $meta['_shipping_city']; ?>">
                </div>
              </div>
            </div>
          </div>

        </div>

        <div class="columns">
        <div class="column">
            <div class="field">
              <label class="label"><?php echo __("Data de Descarga", "bill-faturacao"); ?></label>
              <div class="control">
                <div class="is-fullwidth">
                <input class="input" <?php echo $obrigatorio ? "required" : ""; ?>  <?php echo $obrigatorio ? "name='data_descarga'" : ""; ?> type="datetime-local" data-name="data_descarga">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php echo '</div>';
    }

    public function printShippingAddressTable($meta, $obrigatorio = false)
    {
        echo '<div class="column">'; ?>
      <div class="notification">
      <?php if (!$obrigatorio) {?>
      <div class="is-pulled-right">
        <strong><?php echo __("Utilizar", "bill-faturacao"); ?></strong>
        <input class="tgl tgl-flat moradas" id="valores_armazem" type="checkbox" data-mostra="entrega" <?php echo $obrigatorio ? "checked" : ""; ?> />
        <label class="tgl-btn" for="valores_armazem"></label>
      </div>
      <?php }?>
      <strong><?php echo __("Morada Armazem", "bill-faturacao"); ?></strong>
        <br/><?php echo __("Dados de onde é enviado.", "bill-faturacao"); ?></div>
      <div class="notification moradas" <?php echo $obrigatorio ? "" : "style='display:none'"; ?>>
        <div class="field">
          <label class="label"><?php echo __("Morada", "bill-faturacao"); ?></label>
          <div class="control">
            <div class="is-fullwidth">
              <textarea  <?php echo $obrigatorio ? "required" : ""; ?>  <?php echo $obrigatorio ? "name='carga_morada'" : ""; ?> class="textarea" data-name="carga_morada" cols="30" rows="5"></textarea>
            </div>
          </div>
        </div>

        <div class="columns">
          <div class="column is-4">
            <div class="field">
              <label class="label"><?php echo __("Pais", "bill-faturacao"); ?></label>
              <div class="control">
              <div class="select is-fullwidth">
              <select  <?php echo $obrigatorio ? "required" : ""; ?>  <?php echo $obrigatorio ? "name='carga_pais'" : ""; ?> data-name="carga_pais">
              <?php
foreach ($this->api->getCountriesList() as $code => $name) {
            echo '<option value="' . $code . '"   ' . selected($code, $meta['_shipping_country'], false) . '>' . $name . '</option>';
        }?>
              </select>
              </div>
              </div>
            </div>
          </div>

          <div class="column is-4">
            <div class="field">
              <label class="label"><?php echo __("Código Postal", "bill-faturacao"); ?></label>
              <div class="control">
                <div class="is-fullwidth">
                  <input <?php echo $obrigatorio ? "required" : ""; ?>  <?php echo $obrigatorio ? "name='carga_codigo_postal'" : ""; ?> class="input" type="text" data-name="carga_codigo_postal" value="">
                </div>
              </div>
            </div>
          </div>

          <div class="column is-4">
            <div class="field">
              <label class="label"><?php echo __("Cidade", "bill-faturacao"); ?></label>
              <div class="control">
                <div class="is-fullwidth">
                  <input <?php echo $obrigatorio ? "required" : ""; ?>  <?php echo $obrigatorio ? "name='carga_cidade'" : ""; ?> class="input" type="text" data-name="carga_cidade" value="">
                </div>
              </div>
            </div>
          </div>

        </div>

        <div class="columns">
        <div class="column">
            <div class="field">
              <label class="label"><?php echo __("Data de Carga", "bill-faturacao"); ?></label>
              <div class="control">
                <div class="is-fullwidth">
                <input <?php echo $obrigatorio ? "required" : ""; ?>  <?php echo $obrigatorio ? "name='data_carga'" : ""; ?> class="input" type="datetime-local" data-name="data_carga">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php echo '</div>';
    }

    public function printItemsTable($meta)
    {
        $dados_gerais       = $this->getBasicData();
        $config_por_defeito = $this->getDefaultConfig();
        $order              = new WC_Order((int) $_GET['order']);
        $items              = $order->get_items();
        echo '<div class="column"><div class="notification"><strong>' . __("Linhas do documento", "bill-faturacao") . '</strong>
    <br/>' . __("Aqui são mostrados os itens e portes etc. O campo código equivale ao campo SKU do Woocommerce. Se quiser fazer referência entre os seus produtos no woocommerce e no bill.pt use o campo Sku. Se não utilizar será automáticamente criado um novo produto e actualizado o sku no Woocommerce. Lembre-se que o código deverá ser único.", "bill-faturacao") . '</div><div class="notification">';
        echo '<table class="widefat">
    <thead>
    <tr>
    <th>' . __("Bill.pt Item ID", "bill-faturacao") . '</th>
    <th>' . __("Código", "bill-faturacao") . '</th>
    <th>' . __("Descricão", "bill-faturacao") . '</th>
    <th>' . __("Qtd", "bill-faturacao") . '</th>
    <th>' . __("P. Unit", "bill-faturacao") . '</th>
    <th style="max-width:150px;">Tax</th>
    <th>Tax Total</th>
    <th>Total</th>
    </tr>
    </thead>
    <tfoot>
    <tr>
    <th>' . __("Bill.pt Item ID", "bill-faturacao") . '</th>
    <th>' . __("Código", "bill-faturacao") . '</th>
    <th>' . __("Descricão", "bill-faturacao") . '</th>
    <th>' . __("Qtd", "bill-faturacao") . '</th>
    <th>' . __("P. Unit", "bill-faturacao") . '</th>
    <th style="max-width:150px;">Tax</th>
    <th>Tax Total</th>
    <th>Total</th>
    </tr>
    </tfoot><tbody>';

        foreach ($items as $key => $item) {

            $product = $order->get_product_from_item($item);

            $sku = $product->get_sku();

            $tax_per_unit = $item['line_tax'] / $item['qty'];
            $unit_price   = ($item['line_total'] / $item['qty']);
            $tax          = round(($tax_per_unit * 100) / ($item['line_total'] / $item['qty']));

            $item_id = '<span class="tag is-success">' . __("Novo", "bill-faturacao") . '</span>';

            if (isset($sku) && strlen($sku) > 1) {
                $produto_id = 0;
                $produto_db = $this->getItemFromDB($sku);

                if (isset($produto_db->item_id)) {
                    $produto_id = $produto_db->item_id;
                }
                if ($produto_id == 0) {
                    $produto = $this->getItemByCodigo($sku);
                    if (isset($produto->data[0])) {
                        $produto_id = $produto->data[0]->id;
                        $this->updateItemDB($produto->data[0]);
                    }
                }

                if ($produto_id > 0) {
                    $item_id = '<span class="tag">' . $produto_id . '<input type="hidden" name="produtos[' . $key . '][item_id]" value="' . $produto_id . '" /></span>';
                }
            }

            $unidade_medida_id = isset($config_por_defeito->unidade_medida) ? $config_por_defeito->unidade_medida : '';

            $ProductCategory = 'M';
            $movimenta_stock = 0;

            echo '<tr>';
            echo '<td class="item_id" data-key="' . $key . '">' . $item_id . '</td>';
            echo '<td><div class="field has-addons"><input type="hidden" name="produtos[' . $key . '][unidade_medida_id]" value="' . $unidade_medida_id . '" /><input type="hidden" name="produtos[' . $key . '][movimenta_stock]" value="' . $movimenta_stock . '" /><input type="hidden" name="produtos[' . $key . '][ProductCategory]" value="' . $ProductCategory . '" /><input type="hidden" name="produtos[' . $key . '][product_id]" value="' . $item->get_product_id() . '" /><input type="hidden" name="produtos[' . $key . '][variation_id]" value="' . $item->get_variation_id() . '" /><p class="control"><input class="input is-small sku" value="' . $sku . '" name="produtos[' . $key . '][codigo]" /></p><p class="control"><a class="button procurar-produto">' . __("Procurar", "bill-faturacao") . '</a></p></div></td>';
            echo '<td><input type="hidden" name="produtos[' . $key . '][nome]" value="' . $item->get_name() . '" />' . $item->get_name() . '</td>';
            echo '<td><input type="hidden" name="produtos[' . $key . '][quantidade]" value="' . $item['qty'] . '" />' . $item['qty'] . '</td>';
            echo '<td><input type="hidden" name="produtos[' . $key . '][preco_unitario]" value="' . $unit_price . '" />' . $unit_price . '</td>';
            if ($tax == 0) {
                echo '<td style="max-width:150px;"><div class="select is-fullwidth"><select name="produtos[' . $key . '][isencao]" id="isencao">';
                $this->populateSelectConfig('isencao', $dados_gerais, $config_por_defeito);
                echo '</select><input type="hidden" name="produtos[' . $key . '][imposto_id]" value="0" /></div></td>';
            } else {
                echo '<td><select name="produtos[' . $key . '][imposto]" id="imposto"><option></option>';
                foreach ($dados_gerais['imposto'] as $imposto) {
                    $imposto = json_decode($imposto->value);
                    echo '<option value="' . $imposto->valor . '" ' . $this->isSelected(($tax == $imposto->valor)) . '>' . $imposto->nome . '</option>';
                }
                echo '</select></td>';
            }
            echo '<td>' . $item['line_tax'] . '</td>';
            echo '<td>' . ($item['line_total'] + $item['line_tax']) . '</td>';
            echo '</tr>';
            echo '<tr><td colspan="8"></td>';

        }

        $key += 1;

        if ($order->get_shipping_total() > 0) {
            $sku = isset($config_por_defeito->codigo_portes) ? $config_por_defeito->codigo_portes : '';

            if (isset($sku) && strlen($sku) > 1) {

                $produto_id = 0;
                $produto_db = $this->getItemFromDB($sku);

                if (isset($produto_db->item_id)) {
                    $produto_id = $produto_db->item_id;
                }

                if ($produto_id == 0) {
                    $produto = $this->getItemByCodigo($sku);
                    if (isset($produto->data[0])) {
                        $produto_id = $produto->data[0]->id;
                        $this->updateItemDB($produto->data[0]);
                    }
                }

                if ($produto_id > 0) {
                    $item_id = '<span class="tag">' . $produto_id . '<input type="hidden" name="produtos[' . $key . '][item_id]" value="' . $produto_id . '" /></span>';
                }
            }

            $tax   = round(($order->get_shipping_tax() * 100) / $order->get_shipping_total());
            $price = $order->get_shipping_total();

            echo '<tr>';
            echo '<td class="item_id" data-key="' . $key . '">' . $item_id . '<input type="hidden" name="produtos[' . $key . '][unidade_medida_id]" value="' . $unidade_medida_id . '" /><input type="hidden" name="produtos[' . $key . '][movimenta_stock]" value="0" /><input type="hidden" name="produtos[' . $key . '][ProductCategory]" value="M" /><input type="hidden" name="produtos[' . $key . '][servico]" value="1" /><input type="hidden" name="produtos[' . $key . '][portes]" value="1" /></td>';
            echo '<td><div class="field has-addons"><p class="control"><input type="text" class="input is-small sku" value="' . $sku . '" name="produtos[' . $key . '][codigo]" /></p><p class="control"><a class="button procurar-produto">' . __("Procurar", "bill-faturacao") . '</a></p></div></td>';
            echo '<td><input type="hidden" class="input" value="Portes" name="produtos[' . $key . '][nome]" />Portes</td>';
            echo '<td><input type="hidden" class="input" value="1" name="produtos[' . $key . '][quantidade]" />1</td>';
            echo '<td><input type="hidden" class="input" value="' . $price . '" name="produtos[' . $key . '][preco_unitario]" />' . $price . '</td>';

            if ($tax == 0) {
                echo '<td style="max-width:150px;"><div class="select is-fullwidth"><select name="produtos[' . $key . '][isencao]" id="isencao">';
                $this->populateSelectConfig('isencao', $dados_gerais, $config_por_defeito);
                echo '</select><input type="hidden" name="produtos[' . $key . '][imposto_id]" value="0" /></div></td>';
            } else {
                echo '<td><select name="produtos[' . $key . '][imposto]" id="imposto"><option></option>';
                foreach ($dados_gerais['imposto'] as $imposto) {
                    $imposto = json_decode($imposto->value);
                    echo '<option value="' . $imposto->valor . '" ' . $this->isSelected(($tax == $imposto->valor)) . '>' . $imposto->nome . '</option>';
                }
                echo '</select></td>';
            }
            echo '<td>' . $order->get_shipping_tax() . '</td>';
            echo '<td>' . ($order->get_shipping_total() + $order->get_shipping_tax()) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div></div>';
    }

    public function printTotalsTable($meta, $order)
    {
        echo '<div class="column"><div class="notification"><strong>' . __("Totais", "bill-faturacao") . '</strong>
  <br/>' . __("Totais do Documento", "bill-faturacao") . '</div>';
        echo '<div class="columns">
    <div class="column">
    <div class="notification">
    <table class="widefat"><thead><tr>
    <th></th><th>' . __("Valor", "bill-faturacao") . '</th></tr></thead><tbody>';
        echo '<tr><td style="text-align:right">Sub-Total</td><td>' . ($order->get_total() - $order->get_total_tax()) . '</td></tr>';
        echo '<tr><td style="text-align:right">Tax</td><td>' . $order->get_total_tax() . '</td></tr>';
        echo '<tr><td style="text-align:right">Total</td><td>' . $order->get_total() . '</td></tr>';
        echo '</tbody></table></div></div></div></div>';
    }

    public function isSelected($boolean)
    {
        return $boolean ? 'selected="selected"' : '';
    }

    public function getItemByCodigo($codigo)
    {
        $api = $this->api;
        return $api->getItems([
            'pesquisa' => ['codigo' => $codigo],
        ]);
    }

    public function printCurrencyTable($meta)
    {
        echo '<div class="column">'; ?>

  <div class="notification">
      <strong><?php echo __("Moeda alternativa", "bill-faturacao"); ?></strong><br/><span class="tag is-danger"><?php echo __("Atenção", "bill-faturacao"); ?></span> <?php echo __("Todos os documentos são emitidos", "bill-faturacao"); ?> <strong><?php echo __("em Euros", "bill-faturacao"); ?></strong>. <?php echo __("Pelo que a moeda do seu Woocommerce por defeito deverá ser euros. No entanto poderá mostrar o total no fim do documento numa moeda alternativa.", "bill-faturacao"); ?>
  </div>


  <div class="notification">
    <div class="columns">
      <div class="column">
        <div class="field">
                <label class="label"><?php echo __("Moeda", "bill-faturacao"); ?></label>
              <div class="control">
                  <div class="select is-fullwidth">
                    <select name="moeda">
                      <?php
foreach ($this->api->getCurrencyList() as $code => $name) {
            echo '<option value="' . $code . '">' . $name . '</option>';
        }
        ?>
                    </select>
                  </div>
              </div>
        </div>
      </div>
     <div class="column">
          <div class="field">
            <label class="label"><?php echo __("Cambio", "bill-faturacao"); ?></label>
            <div class="control">
              <div class="is-fullwidth">
                  <input class="input" name="cambio" value="1">
              </div>
            </div>
          </div>
      </div>
     </div>
  </div>

<?php
echo '</div>';
    }

    public function getAllOrders($qty = 15)
    {

        if (isset($_GET['order'])) {
            if (!$this->validateToken()) {
                return;
            }

            $morada_de_entrega = ['fatura', 'guia_e_fatura', 'fatura_recibo'];
            $morada_armazem    = ['fatura', 'guia_e_fatura', 'fatura_recibo'];
            $obrigatorio       = ($_GET['doc'] == "guia_e_fatura") ? true : false;

            $order = new WC_Order((int) $_GET['order']);
            $meta  = $this->getOrderMeta($order->get_order_number());

            echo '<tr><td colspan="9"><div class="notification"><strong>Order: #' . (int) $_GET['order'] . '</strong></div><form id="formulario_criar_documento" method="POST"><div class="columns">';
            $this->printDocumentInfo($meta);
            $this->printUserCompanyInfo($meta);
            echo '</div><div class="columns">';
            if (isset($_GET['doc']) && $_GET['doc'] != "recibo") {
                $this->printItemsTable($meta);
            }
            echo '</div><div class="columns">';
            if (in_array($_GET['doc'], $morada_armazem)) {
                $this->printShippingAddressTable($meta, $obrigatorio);
            }

            if (in_array($_GET['doc'], $morada_de_entrega)) {
                $this->printDeliveryAddressTable($meta, $obrigatorio);
            }
            echo '</div>';

            echo '<div class="columns">';
            $this->printCurrencyTable($meta);
            $this->printTotalsTable($meta, $order);
            echo '</div><input type="hidden" id="terminado" value="0" name="terminado" />';

            echo '<div class="columns">';
            echo '<div class="column"></div><div class="column">
        <div class="group">
        <a href="#" class="two" id="finalizar_documento"><span>' . __("Finalizar", "bill-faturacao") . '</span>
        <div class="bg"></div>
        <a href="#" class="one" id="criar_rascunho">' . __("Criar Rascunho", "bill-faturacao") . '</a>
        </div>
        </div>
        </div>';
            echo '</div></form>';
            echo '</td></tr>';

            return 0;
        } else {
            $orderdata = array();
            $paged     = isset($_GET['paged']) ? (int) $_GET['paged'] : 1;
            $args      = array(
                'post_type'      => 'shop_order',
                'post_status'    => array("wc-processing", "wc-completed"),
                'posts_per_page' => $qty,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'paged'          => $paged,
            );
            $my_query        = new WP_Query($args);
            $customer_orders = $my_query->posts;

            foreach ($customer_orders as $customer_order) {

                $order = new WC_Order($customer_order);
                $meta  = $this->getOrderMeta($order->get_order_number());
                $level = $this->getDocumentLevel($meta);

                $orderdata[] = array("info" => $meta, "id" => $order->get_order_number());
                $order_id    = $order->get_order_number();
                echo '<tr>
            <td>#' . $order_id . '</td>
            <td>' . $meta["_shipping_first_name"] . ' ' . $meta["_shipping_last_name"] . '</td>
            <td>' . $meta["_order_total"] . '</td>
            <td>' . $this->documentState($order_id, ($level >= 1), 'orcamento', $meta) . '</td>
            <td>' . $this->documentState($order_id, ($level >= 2), 'encomenda', $meta) . '</td>
            <td>' . $this->documentState($order_id, ($level >= 3), 'guia_e_fatura', $meta) . '</td>
            <td>' . $this->documentState($order_id, ($level >= 3), 'fatura', $meta) . '</td>
            <td>' . $this->documentState($order_id, ($level >= 3), 'fatura_recibo', $meta) . '</a></td>
            <td>' . $this->documentState($order_id, ($level >= 3), 'fatura_simplificada', $meta) . '</td>
            <td>' . $this->documentState($order_id, ($level >= 4), 'recibo', $meta) . '</td>
            <td>' . $meta["_paid_date"] . '</td>
            </tr>';

            }

            return $my_query->max_num_pages;
        }
    }

    public function createDocument()
    {
        if (!isset($_GET['order'])) {
            return false;
        }

        $order_id = (int) $_GET['order'];

        if (isset($_POST['criar_documento'])) {
            if (!$this->validateToken()) {
                return;
            }

            $api = $this->api;

            if (isset($_POST['data'])) {
                if (strlen($_POST['data']) > 8) {
                    $_POST['data'] = str_replace("T", " ", $_POST['data']) . ':00';
                    if (!$api->isValidDateTime($_POST['data'])) {
                        if (isset($documento->error)) {
                            $this->addError(__("Data em formato invalido", "bill-faturacao") . sanitize_text_field(strip_tags($_POST['data'])));
                        }
                        $this->printErrors();
                        return;
                    }
                }
            }

            if (isset($_POST['prazo_vencimento'])) {
                if (strlen($_POST['prazo_vencimento']) > 8) {
                    $_POST['prazo_vencimento'] = str_replace("T", " ", $_POST['prazo_vencimento']) . ':00';
                    if (!$api->isValidDateTime($_POST['prazo_vencimento'])) {
                        if (isset($documento->error)) {
                            $this->addError(__("Prazo Vencimento formato invalido", "bill-faturacao") . sanitize_text_field(strip_tags($_POST['prazo_vencimento'])));
                        }
                        $this->printErrors();
                        return;
                    }
                }
            }

            if (isset($_POST['data_carga'])) {
                if (strlen($_POST['data_carga']) > 8) {
                    $_POST['data_carga'] = str_replace("T", " ", $_POST['data_carga']) . ':00';
                    if (!$api->isValidDateTime($_POST['data_carga'])) {
                        if (isset($documento->error)) {
                            $this->addError(__("Data Carga em formato invalido", "bill-faturacao") . sanitize_text_field(strip_tags($_POST['data_carga'])));
                        }
                        $this->printErrors();
                        return;
                    }
                }
            }

            if (isset($_POST['data_descarga'])) {
                if (strlen($_POST['data_descarga']) > 8) {
                    $_POST['data_descarga'] = str_replace("T", " ", $_POST['data_descarga']) . ':00';
                    if (!$api->isValidDateTime($_POST['data_descarga'])) {
                        if (isset($documento->error)) {
                            $this->addError(__("Data Descarga em formato invalido", "bill-faturacao") . sanitize_text_field(strip_tags($_POST['data_descarga'])));
                        }
                        $this->printErrors();
                        return;
                    }
                }
            }

            if ($_POST['tipo_documento'] != "recibo") {
                $_POST['contato']  = (array) $this->createContato($_POST['contato']);
                $_POST['produtos'] = $this->createItems($_POST['produtos']);
            }

            if (in_array($_POST['tipo_documento'], ['guia_e_fatura', 'fatura', 'fatura_recibo']) && $_POST['contato']['pais'] == "PT") {

                if (isset($_POST['codigo_postal'])) {
                    if (!$api->isValidZipCode($_POST['codigo_postal'])) {
                        $this->addError(__("Codigo Postal Invalido, se não tiver a informação deixe em branco.", "bill-faturacao") . sanitize_text_field(strip_tags($_POST['codigo_postal'])));
                        $this->printErrors();
                        return;
                    }
                }

                if (isset($_POST['carga_codigo_postal'])) {
                    if (!$api->isValidZipCode($_POST['carga_codigo_postal'])) {
                        $this->addError(__("Codigo Postal do armazem invalido (é apenas obrigado a colocar os dados do armazem em documentos de transporte) :", "bill-faturacao") . sanitize_text_field(strip_tags($_POST['carga_codigo_postal'])));
                        $this->printErrors();
                        return;
                    }
                }

                if (isset($_POST['carga_codigo_postal'])) {
                    if (!$api->isValidZipCode($_POST['descarga_codigo_postal'])) {
                        $this->addError(__("Codigo Postal do destino invalido (é apenas obrigado a colocar os dados do destino em documentos de transporte) :", "bill-faturacao") . sanitize_text_field(strip_tags($_POST['descarga_codigo_postal'])));
                        $this->printErrors();
                        return;
                    }
                }

            }

            switch ($_POST['tipo_documento']) {
                case 'orcamento':
                    $_POST['tipificacao'] = 'ORC';
                    $documento            = $api->createDocument($_POST);
                    if (isset($documento->id)) {
                        $this->updateMeta($order_id, '_orcamento_id', (int) $documento->id);
                        if (isset($documento->token_download)) {
                            $this->updateMeta($order_id, '_orcamento_token', sanitize_text_field($documento->token_download));
                        }
                    }
                    break;
                case 'encomenda':
                    $_POST['tipificacao'] = 'NENC';
                    $documento            = $api->createDocument($_POST);
                    if (isset($documento->id)) {
                        $this->updateMeta($order_id, '_encomenda_id', (int) $documento->id);
                        if (isset($documento->token_download)) {
                            $this->updateMeta($order_id, '_encomenda_token', sanitize_text_field($documento->token_download));
                        }
                    }
                    break;
                case 'guia_e_fatura':
                    $_POST['tipificacao'] = 'GT';
                    $documento            = $api->createDocument($_POST);
                    if (isset($documento->id)) {
                        $this->updateMeta($order_id, '_guia_id', (int) $documento->id);
                        if (isset($documento->token_download)) {
                            $this->updateMeta($order_id, '_guia_token', sanitize_text_field($documento->token_download));
                        }
                        $this->updateMeta($order_id, '_guia_terminado', (int) $documento->terminado);

                        if ($documento->terminado) {
                            $documento = $api->convertDocumentWithID($documento->id, 'FT', $_POST['data']);
                        }

                        if (isset($documento->id)) {
                            $this->updateMeta($order_id, '_fatura_id', (int) $documento->id);
                            if (isset($documento->token_download)) {
                                $this->updateMeta($order_id, '_fatura_token', sanitize_text_field($documento->token_download));
                            }
                            $this->updateMeta($order_id, '_fatura_terminado', (int) $documento->terminado);
                        }
                    }
                    break;
                case 'fatura':
                    $_POST['tipificacao'] = 'FT';
                    $documento            = $api->createDocument($_POST);
                    if (isset($documento->id)) {
                        $this->updateMeta($order_id, '_fatura_id', (int) $documento->id);
                        if (isset($documento->token_download)) {
                            $this->updateMeta($order_id, '_fatura_token', sanitize_text_field($documento->token_download));
                        }
                        $this->updateMeta($order_id, '_fatura_terminado', (int) $documento->terminado);
                    }
                    break;
                case 'fatura_recibo':
                    $_POST['tipificacao'] = 'FR';
                    $documento            = $api->createDocument($_POST);
                    if (isset($documento->id)) {
                        $this->updateMeta($order_id, '_fatura_recibo_id', (int) $documento->id);
                        if (isset($documento->token_download)) {
                            $this->updateMeta($order_id, '_fatura_recibo_token', sanitize_text_field($documento->token_download));
                        }
                        $this->updateMeta($order_id, '_fatura_terminado', (int) $documento->terminado);
                    }
                    break;
                case 'fatura_simplificada':
                    $_POST['tipificacao'] = 'FS';
                    $documento            = $api->createDocument($_POST);
                    if (isset($documento->id)) {
                        $this->updateMeta($order_id, '_fatura_simplificada_id', (int) $documento->id);
                        if (isset($documento->token_download)) {
                            $this->updateMeta($order_id, '_fatura_simplificada_token', sanitize_text_field($documento->token_download));
                        }
                        $this->updateMeta($order_id, '_fatura_terminado', (int) $documento->terminado);
                    }
                    break;
                case 'recibo':
                    $_POST['tipificacao'] = 'RC';
                    $documento_id         = get_post_meta($order_id, '_fatura_id', true);
                    $documento            = $api->createReceiptToDocumentWithID($documento_id);
                    if (isset($documento->id)) {
                        $this->updateMeta($order_id, '_recibo_id', (int) $documento->id);
                    }
                    break;
            }
            if ($api->success()) {
                if (isset($documento->id)) {

                    if (isset($_POST['envio_email']) && $_POST['envio_email'] == "1" && isset($_POST['contato']['email']) && filter_var($_POST['contato']['email'], FILTER_VALIDATE_EMAIL) && $documento->terminado) {
                        $email = $api->emailDocument(['email' => sanitize_email($_POST['contato']['email']), 'id' => (int) $documento->id]);

                        if ($api->success()) {
                            $this->updateMeta((int) $order_id, '_email_enviado_' . sanitize_text_field($documento->id), 'sim');

                            echo '<div class="notification is-success">' . __("E-mail enviado com sucesso para o seguinte endereço: ", "bill-faturacao") . sanitize_text_field($_POST['contato']['email']) . '</div>';
                        }
                    }

                    $_GET['tab'] = "documento";
                    return $documento;
                }
            }

            if (isset($documento->error)) {
                $this->addError($documento->error);
            }

            $this->printErrors();
        }
    }

    public function getContatoCodigo($nif, $email)
    {
        global $wpdb;
        $nif     = sanitize_text_field($nif);
        $email   = sanitize_email($email);
        $contato = $wpdb->get_row("SELECT * FROM bill_contatos WHERE nif = '$nif' OR email = '$email'");

        return isset($contato->codigo) ? $contato->codigo : '';
    }

    public function createContato($data)
    {

        if (isset($data['codigo']) && strlen($data['codigo']) > 0) {
            return $data; #já tem código não vai precisar criar
        }

        global $wpdb;
        $api = $this->api;

        if ($data['pais'] == "PT" && isset($data['nif']) && strlen($data['nif']) > 0) {
            if (!$this->isValidNif($data['nif'])) {
                $this->addError(__("O nif que colocou é invalido. Se não tem o NIF do cliente deixe o campo vazio.", "bill-faturacao"));
                return $data;
            }
        }

        if (isset($data['nif']) && strlen($data['nif']) > 8) {
            $contato = $api->getContacts(['pesquisa' => [
                'nif' => $data['nif'],
            ]]);

            if (isset($contato->data[0]->id)) {
                $wpdb->insert('bill_contatos', [
                    'nif'    => sanitize_text_field($contato->data[0]->nif),
                    'codigo' => sanitize_text_field($contato->data[0]->codigo),
                    'email'  => sanitize_email($contato->data[0]->email),
                ], ['%s', '%s', '%s']);

                return $contato->data[0];
            }
        }

        if (isset($data['email']) && strlen($data['email']) > 5) {
            $contato = $api->getContacts(['pesquisa' => [
                'email' => $data['email'],
            ]]);

            if (isset($contato->data[0]->id)) {
                $wpdb->insert('bill_contatos', [
                    'nif'    => sanitize_text_field($contato->data[0]->nif),
                    'codigo' => sanitize_text_field($contato->data[0]->codigo),
                    'email'  => sanitize_email($contato->data[0]->email),
                ], ['%s', '%s', '%s']);

                return $contato->data[0];
            }
        }

        $contato = $api->createContact($data);
        if (strlen($contato->email) > 0) {
            $wpdb->delete('bill_contatos', [
                'email' => sanitize_email($contato->email),
            ]);
        }
        if (strlen($contato->codigo) > 0) {
            $wpdb->delete('bill_contatos', [
                'codigo' => sanitize_text_field($contato->codigo),
            ]);
        }
        if ($contato->nif != "999999990") {
            $wpdb->delete('bill_contatos', [
                'nif' => sanitize_text_field($contato->nif),
            ]);
        }
        #nao encontrou então cria novo e grava na BD
        $wpdb->insert('bill_contatos', [
            'nif'    => sanitize_text_field($contato->nif),
            'codigo' => sanitize_text_field($contato->codigo),
            'email'  => sanitize_email($contato->email),
        ], ['%s', '%s', '%s']);

        return $contato;
    }

    public function createItems($produtos)
    {
        $api = $this->api;

        foreach ($produtos as $key => $produto) {
            if (isset($produto['item_id'])) {
                continue;
            }

            $product_id   = sanitize_text_field($produtos[$key]['product_id']);
            $variation_id = sanitize_text_field($produtos[$key]['variation_id']);

            if (isset($produto['codigo']) && strlen($produto['codigo']) > 0) {
                $produto = $this->getItemByCodigo(sanitize_text_field($produto['codigo']));

                if (!$api->success()) {
                    $this->addError($produto->error);
                    $this->printErrors();
                    die();
                }

                if (isset($produto->data[0])) {
                    $produtos[$key]['item_id'] = (int) $produto->data[0]->id;

                    $product_id   = sanitize_text_field($produtos[$key]['product_id']);
                    $variation_id = sanitize_text_field($produtos[$key]['variation_id']);

                    $item = wc_get_product($product_id);

                    if (strlen($item->get_sku()) == 0) {
                        $item->set_sku(sanitize_text_field($produto->codigo));
                        $item->save();
                    }

                    if ($variation_id > 0) {
                        $item = wc_get_product($variation_id);
                        $item->set_sku(sanitize_text_field($produto->codigo));
                        $item->save();
                    }

                    continue;
                }
            }

            $portes = false;

            if (isset($produtos[$key]['codigo']) && strlen($produtos[$key]['codigo']) == 0) {
                unset($produtos[$key]['codigo']);
                unset($produto['codigo']);
            }

            if (isset($produtos[$key]['portes']) && !isset($produtos[$key]['item_id']) && !isset($produtos[$key]['codigo'])) {
                $portes = true;
            }

            $produto['unidade_medida_id'] = ($produto['unidade_medida_id'] == "0") ? $this->getUnidadeMedidaID() : sanitize_text_field($produto['unidade_medida_id']);

            if ($portes) {
                $produto['imposto_id'] = !isset($produto['imposto_id']) ? $this->getImpostoID() : (int) $produto['imposto_id'];
            }

            if ($produto['unidade_medida_id'] == "0") {
                unset($produto['unidade_medida_id']);
            }

            $produto_data = array_merge($produto, ['iva_compra' => 0, 'descricao' => $produto['nome']]);
            $produto      = $api->createItem($produto_data);

            if (!$api->success()) {
                $this->addError($produto->error);
                $this->printErrors();
                die();
            }

            if ($portes) {
                global $wpdb;
                $config                = $this->getDefaultConfig();
                $config->codigo_portes = sanitize_text_field($produto->codigo);
                $wpdb->delete('bill_config', array('config' => 'default_config'));
                $wpdb->insert('bill_config', [
                    'config' => 'default_config', 'value' => json_encode($config)], ['%s', '%s']);
            }

            $produtos[$key]['item_id'] = (int) $produto->id;

            if (!$portes) {
                $item = wc_get_product($product_id);

                if (strlen($item->get_sku()) == 0) {
                    $item->set_sku(sanitize_text_field($produto->codigo));
                    $item->save();
                }

                if ($variation_id > 0) {
                    $item = wc_get_product($variation_id);
                    $item->set_sku(sanitize_text_field($produto->codigo));
                    $item->save();
                }
            }

        }

        return $produtos;
    }

    public function printDocument()
    {
        if (!isset($_GET['order']) || !isset($_GET['tab']) || $_GET['tab'] != 'documento') {
            return;
        }
        $order_id = (int) $_GET['order'];
        $doc      = sanitize_text_field($_GET['doc']);
        $api      = $this->api;
        $meta     = $this->getOrderMeta($order_id);
        switch ($doc) {
            case 'orcamento':
                $document = $meta['_orcamento_id'];
                break;
            case 'encomenda':
                $document = $meta['_encomenda_id'];
                break;
            case 'fatura':
                $document = $meta['_fatura_id'];
                break;
            case 'guia':
                $document = $meta['_guia_id'];
                break;
            case 'guia_e_fatura':
                $document = $meta['_guia_id'];
                break;
            case 'fatura_simplificada':
                $document = $meta['_fatura_simplificada_id'];
                break;
            case 'fatura_recibo':
                $document = $meta['_fatura_recibo_id'];
                break;
            case 'recibo':
                $document = $meta['_recibo_id'];
                break;
            default:
                # code...
                break;
        }

        $documento = $api->getDocumentWithID($document);

        echo '<style>
  .container {
    width: 100%;
    max-width: 900px;
    padding-right: 20px;
    padding-left: 20px;
  }
  .invoice {
    background-color: #ffffff;
    border-radius: 10px;
    box-shadow: 0 14px 28px rgba(0, 0, 0, 0.1), 0 10px 10px rgba(0, 0, 0, 0.13);
    margin: 50px 0;
    padding: 50px 30px 30px;
  }
  .invoice header {
    overflow: hidden;
    margin-bottom: 60px;
  }
  .invoice header section:nth-of-type(1) {
    float: left;
  }
  .invoice header section:nth-of-type(1) h1 {
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 2px;
    color: #344760;
    font-size: 25px;
    margin-top: 0;
    margin-bottom: 5px;
  }
  .invoice header section:nth-of-type(1) span {
    color: #b7bcc3;
    font-size: 14px;
    letter-spacing: 2px;
  }
  .invoice header section:nth-of-type(2) {
    float: right;
  }
  .invoice header section:nth-of-type(2) span {
    font-size: 21px;
    color: #b7bcc3;
    letter-spacing: 1px;
  }
  .invoice header section:nth-of-type(2) span:before {
    content: "#";
  }
  .invoice main {
    border: 1px dashed #b7bcc3;
    border-left-width: 0px;
    border-right-width: 0px;
    padding-top: 30px;
    padding-bottom: 30px;
  }
  .invoice main section {
    overflow: hidden;
  }
  .invoice main section span {
    float: left;
    color: #344760;
    font-size: 16px;
    letter-spacing: .5px;
  }
  .invoice main section span:nth-of-type(1) {
    width: 40%;
    margin-right: 5%;
  }
  .invoice main section span:nth-of-type(2) {
    width: calc(50% / 4);
    margin-right: 1%;
  }
  .invoice main section span:nth-of-type(3) , .invoice main section span:nth-of-type(4), .invoice main section span:nth-of-type(5)  {
    text-align: right;
  }
  .invoice main section span:nth-of-type(3) , .invoice main section span:nth-of-type(4), .invoice main section span:nth-of-type(5)  {
     width: calc(50% / 4);
    margin-right: 1%;
  }

  .invoice main section:nth-of-type(1) {
    margin-bottom: 30px;
  }
  .invoice main section:nth-of-type(1) span {
    color: #b7bcc3;
    text-transform: uppercase;
    letter-spacing: 2px;
    font-size: 13px;
  }
  .invoice main section:nth-of-type(2) {
    margin-bottom: 30px;
  }
  .invoice main section:nth-of-type(2) figure {
    overflow: hidden;
    margin: 0;
    margin-bottom: 20px;
    line-height: 160%;
  }
  .invoice main section:nth-of-type(2) figure:last-of-type {
    margin-bottom: 0;
  }
  .invoice main section:nth-of-type(3) span:nth-of-type(1), .invoice main section:nth-of-type(4) span:nth-of-type(1),
  .invoice main section:nth-of-type(5) span:nth-of-type(1),
  .invoice main section:nth-of-type(6) span:nth-of-type(1)   {
    width: 72.5%;
    font-weight: bold;
    text-align:right;
  }
  .invoice main section:nth-of-type(3) span:nth-of-type(2),
  .invoice main section:nth-of-type(4) span:nth-of-type(2),
  .invoice main section:nth-of-type(5) span:nth-of-type(2),
  .invoice main section:nth-of-type(6) span:nth-of-type(2) {
    margin-right: 0 !important;
    text-align:right;
  }

  .invoice footer {
    text-align: center;
    margin-top: 30px;
  }
  .invoice footer a {
    font-size: 19px;
    font-weight: bold;
    text-decoration: none;
    text-transform: uppercase;
    position: relative;
    letter-spacing: 1px;
    width: 30%;
    display: inline-block;
  }
  .invoice footer a:after {
    content: "";
    width: 0%;
    height: 4px;
    position: absolute;
    right: 0;
    bottom: -10px;
    background-color: inherit;
    -webkit-transition: width 0.2s ease-in-out;
    -moz-transition: width 0.2s ease-in-out;
    transition: width 0.2s ease-in-out;
  }
  .invoice footer a:hover:after {
    width: 100%;
  }
  .invoice footer a:nth-of-type(1) {
    color: #b7bcc3;
    margin-right: 30px;
  }
  .invoice footer a:nth-of-type(1):after {
    background-color: #b7bcc3;
  }
  .invoice footer a:nth-of-type(2) {
    color: #fe8888;
  }
  .invoice footer a:nth-of-type(2):after {
    background-color: #fe8888;
  }

  </style>';
        $categoria_fatura = ['recibo', 'fatura_simplificada', 'fatura_recibo'];
        $tipo             = ($doc == "guia_e_fatura") ? "guia" : $doc;
        $tipo             = in_array($doc, $categoria_fatura) ? "fatura" : $doc;

        echo '<div class="container"><div class="invoice">';
        $this->printDocumentTable($documento);
        $this->printContactTable($documento);
        if ($doc != "recibo") {
            $this->printProductTable($documento);
        }
        $this->printInvoiceTotalTable($documento);
        $this->printOptions($documento, $meta, $order_id, $tipo);
        echo '</div></div>';

        if ($doc == "guia_e_fatura") {
            $documento = $api->getDocumentWithID($meta['_fatura_id']);
            echo '<div class="container"><div class="invoice">';
            $this->printDocumentTable($documento);
            $this->printContactTable($documento);
            $this->printProductTable($documento);
            $this->printInvoiceTotalTable($documento);
            $this->printOptions($documento, $meta, $order_id, "fatura");
            echo '</div></div>';
        }
    }

    public function printDocumentTable($data)
    {
        $estado = !$data->terminado ? "Rascunho" : "Finalizado";
        echo '<header>
        <section>
          <h1>' . sanitize_text_field($data->invoice_number) . '</h1>
          <span>' . sanitize_text_field($data->invoice_date) . '</span>
        </section>

        <section>
          <span>' . sanitize_text_field($estado) . '</span>
        </section><br clear="all" />';
    }

    public function printContactTable($data)
    {

        echo '<div>
        <section>
          <h1>' . sanitize_text_field($data->contato->nome) . '</h1>
          <span>VAT/NIF: ' . sanitize_text_field($data->contato->nif) . '</span>
          <p>' . sanitize_text_field($data->contato->morada) . '</p>
          <p>' . sanitize_text_field($data->contato->cidade) . '</p>
          <p>' . sanitize_text_field($data->contato->codigo_postal) . '</p>
          <p>' . sanitize_text_field($data->contato->pais) . '</p>
        </section>
        </div>
      </header>';
    }

    public function printProductTable($data)
    {
        echo '<main>
  <section>
    <span>' . __("Produto", "bill-faturacao") . '</span>
    <span>' . __("Qtd", "bill-faturacao") . '</span>
    <span>' . __("Preco", "bill-faturacao") . '</span>
    <span>' . __("Imposto", "bill-faturacao") . '</span>
    <span>' . __("Total", "bill-faturacao") . '</span>
  </section><section>';
        foreach ($data->lancamentos as $lancamento) {
            echo "<figure>
      <span>" . sanitize_text_field($lancamento->nome) . "</span>
      <span>" . round($lancamento->quantidade, 2) . "</span>
      <span>" . round($lancamento->preco_unitario, 2) . "</span>
      <span>" . sanitize_text_field($lancamento->tax_total) . "</span>
      <span>" . sanitize_text_field($lancamento->gross_total) . "</span>
    </figure>";
        }
        echo '</section>';
    }

    public function printInvoiceTotalTable($data)
    {
        echo "<section>
            <span>" . __("Sub-total", "bill-faturacao") . "</span>
            <span>" . sanitize_text_field($data->net_total) . "</span>
          </section>
          <section>
             <span>" . __("Imposto", "bill-faturacao") . "</span>
             <span>" . sanitize_text_field($data->tax_total) . "</span>
          </section>
          <section>
              <span>" . __("Descontos", "bill-faturacao") . "</span>
              <span>" . sanitize_text_field($data->desconto_total) . "</span>
            </section>
            <section>
            <span>" . __("Total", "bill-faturacao") . "</span>
            <span>" . sanitize_text_field($data->gross_total) . "</span>
          </section>
        </main>";
    }

    public function printOptions($data, $meta, $order_id, $doc)
    {
        $document_level = $this->getDocumentLevel($meta);
        echo '<footer>';
        echo '<a href="' . $this->getUrl() . '/documentos/editar/' . $doc . 's/' . $data->id . '">' . __("Ver No Bill", "bill-faturacao") . '</a>';

        if ($data->terminado == 1 && (!isset($meta['_email_enviado_' . $data->id]) || $meta['_email_enviado_' . $data->id] != "sim") && isset($meta["_billing_email"]) && filter_var($meta["_billing_email"], FILTER_VALIDATE_EMAIL)) {
            echo '<a href="admin.php?page=bill_settings&tab=encomendas&order_id=' . $order_id . '&envio_email=' . $data->id . '&m=' . $meta["_billing_email"] . '">' . __("Enviar Por E-mail", "bill-faturacao") . '</a>';
        }
        if ($data->terminado == 1 && $document_level == 3 && $doc != "guia") {
            echo '<a href="admin.php?page=bill_settings&tab=encomendas&order=' . $order_id . '&doc=recibo">' . __("Gerar Recibo", "bill-faturacao") . '</a>';
        }
        echo '</footer>';
    }

    public function sendEmail()
    {
        if (!isset($_GET["m"]) ||
            !filter_var($_GET["m"], FILTER_VALIDATE_EMAIL)) {
            return;
        }

        if (!isset($_GET["envio_email"]) ||
            !is_numeric($_GET["envio_email"])) {
            return;
        }

        if (!isset($_GET["order_id"]) ||
            !is_numeric($_GET["order_id"])) {
            return;
        }

        if (!$this->validateToken()) {
            return;
        }

        $api          = $this->api;
        $documento_id = (int) $_GET["envio_email"];
        $api->emailDocument(['email' => $_GET["m"], 'id' => $documento_id]);

        if ($api->success()) {
            $this->updateMeta((int) $_GET["order_id"], '_email_enviado_' . $documento_id, 'sim');

            echo '<div class="notification is-success">' . __("E-mail enviado com sucesso para o seguinte endereço:", "bill-faturacao") . ' ' . sanitize_text_field($_GET['m']) . '</div>';
        }
    }

    public function getOrderMeta($postID = '')
    {
        global $wpdb;
        if ($postID != '') {
            $postID  = (int) $postID;
            $results = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "postmeta WHERE post_id = '$postID'", ARRAY_A);
            foreach ($results as $user_r) {
                $userInfo[(string) $user_r['meta_key']] = $user_r['meta_value'];
            }
            return ($userInfo);
        } else {
            echo __("Utilizador não encontrado no Woocommerce!", "bill-faturacao");
        }
    }

    public function getDocumentLevel($meta)
    {
        $level = 0;
        if (isset($meta['_orcamento_id'])) {
            $level = 1;
        }
        if (isset($meta['_encomenda_id'])) {
            $level = 2;
        }
        if (isset($meta['_guia_id'])) {
            $level = 3;
        }
        if (isset($meta['_fatura_id'])) {
            $level = 3;
        }
        if (isset($meta['_fatura_simplificada_id'])) {
            $level = 4;
        }
        if (isset($meta['_fatura_recibo_id'])) {
            $level = 4;
        }
        if (isset($meta['_recibo_id'])) {
            $level = 4;
        }
        return $level;
    }

    protected function updateMeta($order_id, $meta_name, $value)
    {
        update_post_meta($order_id, $meta_name, sanitize_text_field($value));
    }

}