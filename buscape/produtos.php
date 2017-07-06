<?php

error_reporting(0);

// conexao do joomla
define('_JEXEC', 1);
define( 'DS', DIRECTORY_SEPARATOR );
define( 'JPATH_BASE', realpath(dirname(__FILE__)).DS.'..'.DS);

require_once JPATH_BASE .DS.'includes'.DS.'defines.php';
require_once JPATH_BASE .DS.'includes'.DS.'framework.php';

// Instantiate the application.
$app 	= JFactory::getApplication('site');
$url 	= JURI::root();
$cache 	=  JFactory::getCache();
$cache->setCaching( 0 );
ob_clean();
header ("Content-Type:text/xml");  


// configurações do VirtueMart
if (!class_exists( 'VmConfig' )) require(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_virtuemart'.DS.'helpers'.DS.'config.php');
$config = VmConfig::loadConfig();

$product_model 	= VmModel::getModel('product');

if(!class_exists('CurrencyDisplay')) require(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_virtuemart'.DS.'helpers'.DS.'currencydisplay.php');		
$currency = CurrencyDisplay::getInstance(null);

if (!class_exists('VmImage')) require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'image.php');

if (!class_exists('parcelamentoWeber')) require(JPATH_SITE.DS.'plugins'.DS.'system'.DS.'vmparcelamento'.DS.'lib'.DS.'parcelamento.php');

$lang 	= 'pt_br';
$db 	= JFactory::getDBO(); 
$query  = "SELECT * FROM #__virtuemart_products p WHERE p.published = 1 ";
$db->setQuery($query);
$produtos 		= $db->loadObjectList(); 

// preço do produto
$params 				= JComponentHelper::getParams('com_templateparcelamento');
$modelo 				= $params->get('modelo');
$parcelasComJuros 		= $params->get('parcelasComJuros');
$parcelasSemJuros 		= $params->get('parcelasSemJuros');
$tipo_parcelamento		= $params->get('tipo_parcelamento');
$show_boleto 			= $params->get('show_boleto');
$show_deposito 			= $params->get('show_deposito');
$parcelaMinima 			= $params->get('parcelaMinima');
$valorMinimoSemJuros 	= $params->get('valorMinimoSemJuros');
$fundoHoverTab 			= $params->get('fundoHoverTab');
$fundoActiveTab 		= $params->get('fundoActiveTab');
$show_desconto			= $params->get('show_desconto');
$descontoAvista			= $params->get('descontoAVista');
$textoDescontoAvista	= $params->get('textoDescontoAVista');
$porcentagem			= $params->get('tamanhoTabelaParcelamento');
$descontoBoleto			= $params->get('descontoBoleto');
$descontoDeposito		= $params->get('descontoDeposito');
$avancado_parcela		= $params->get('avancado_parcela');	
$tipo_sem_juros			= $params->get('tipo_sem_juros');

$conteudo 				= '';
foreach ($produtos as $key => $item) {

	$product = $product_model->getProduct($item->virtuemart_product_id);
	$product_model->addImages($product);
	$url_produto 		= str_replace('/buscape','',JRoute::_(JURI::base()."index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=".$product->virtuemart_product_id, true));

	if (isset($product->images['0']->file_url_thumb)) {
		$imagem_produto 	= str_replace('buscape/','',$url.$product->images['0']->file_url_thumb);
	} else {
		$imagem_produto 	= "";
	}



// 	$categoria_produto 	= $product->categoryItem['0']->category_name;
        $categoria_produto 	= $product->category_name;

	if ($product->prices['override']) {
		$preco_produto  = $product->prices['product_override_price'];
	} else {
		$preco_produto  = $product->prices['salesPrice'];
	}

	// parcelamento
	$parcelamento = new parcelamentoWeber(
		null,
		$preco_produto,
		$parcelasComJuros,
		$parcelasSemJuros,
		$tipo_parcelamento,
		$modelo,
		$show_boleto,
		$show_deposito,
		$parcelaMinima,
		$valorMinimoSemJuros,
		$show_desconto,
		$descontoAvista,
		$textoDescontoAvista,
		$porcentagem,
		$descontoBoleto,
		$descontoDeposito,
		$avancado_parcela,
		$tipo_sem_juros,
		$fundoActiveTab,
		$fundoHoverTab,
		$tarifaEspecialParcelamento
	);

	$desc_parcelamento = utf8_decode(str_replace(array('&nbsp;','ou'),array(' ',''),strip_tags($parcelamento->mostraParcela())));	

	$conteudo .= "<produto>
					<descricao>".addslashes(utf8_decode($product->product_name))."</descricao>	
					<canal_buscape>
						<canal_url>".htmlentities($url_produto)."</canal_url>
						<valores>
							<valor>
								<forma_de_pagamento>cartao_parcelado_com_juros</forma_de_pagamento>
								<parcelamento>".$desc_parcelamento."</parcelamento>
								<canal_preco>".$currency->priceDisplay($preco_produto,0,true)."</canal_preco>
							</valor>
						</valores>
					</canal_buscape>
					<canal_lomadee>
						<canal_url>".htmlentities($url_produto)."</canal_url>
						<valores>
							<valor>
								<forma_de_pagamento>cartao_parcelado_com_juros</forma_de_pagamento>
								<parcelamento>".$desc_parcelamento."</parcelamento>
								<canal_preco>".$currency->priceDisplay($preco_produto,0,true)."</canal_preco>
							</valor>
						</valores>
					</canal_lomadee>
					<id_oferta>".($product->product_sku)."</id_oferta>
					<imagens>
						<imagem tipo=\"O\">".$imagem_produto."</imagem>
		            </imagens>
					<categoria>".$categoria_produto."</categoria>
					<isbn></isbn>
					<cod_barra></cod_barra>
					<disponibilidade>".$product->product_in_stock."</disponibilidade>
		            <marketplace>false</marketplace>
		  	        <marketplace_nomeparceiro></marketplace_nomeparceiro>
				</produto>";
}

$cabecalho_xml 	= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?><buscape><data_atualizacao>".(date('Y-m-dTH:i:s').'GMT-3')."</data_atualizacao><produtos>";
$rodape_xml 	= "</produtos></buscape>";

echo $cabecalho_xml;
echo $conteudo;
echo $rodape_xml;

exit;