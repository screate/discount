<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include.php");
CModule::IncludeModule('iblock');
CModule::IncludeModule('highloadblock');

// Каталог товаров
define('CATALOG_IB', 2);

use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

$filter = array(
	'select' => array('ID', 'NAME', 'TABLE_NAME', 'FIELDS_COUNT'),
	'filter' => array('NAME' => 'UserDiscounts')
);
$hlblock = HL\HighloadBlockTable::getList($filter)->fetch();
if (!empty($hlblock))
{
	$hlblockID = $hlblock['ID'];
	ShowNote('HighloadBlock уже создан!');
}
else
{
	$data = array(
		'NAME' => 'UserDiscounts',
		'TABLE_NAME' => 'custom_user_discounts'
	);
	
	// create
	$result = HL\HighloadBlockTable::add($data);
	$hlblockID = $result->getId();
	
	ShowNote('HighloadBlock создан!');
	
	$obUserField  = new CUserTypeEntity();
	
	$arFieldsUF = Array(
			"ENTITY_ID" => "HLBLOCK_".$hlblockID,
			"FIELD_NAME" => "UF_USER_ID",
			"USER_TYPE_ID" => "integer",
			"XML_ID" => "",
			"SORT" => "100",
			"MULTIPLE" => "",
			"MANDATORY" => "",
			"SHOW_FILTER" => "N",
			"SHOW_IN_LIST" => "",
			"EDIT_IN_LIST" => "",
			"IS_SEARCHABLE" => "",
			"SETTINGS" => Array (
				"DAFAULT_VALUE" => Array ()
			),
			"EDIT_FORM_LABEL" => array(
				"ru" => "Пользователь",
				"en" => "User",
			),
			"LIST_COLUMN_LABEL" => array(
				"ru" => "Пользователь",
				"en" => "User",
			),
			"LIST_FILTER_LABEL" => array(
				"ru" => "Пользователь",
				"en" => "User",
			),
			"ERROR_MESSAGE" => array(
				"ru" => "",
				"en" => "",
			),
			"HELP_MESSAGE" => array(
				"ru" => "",
				"en" => "",
			)
	);
	
	
	$enID = $obUserField->Add($arFieldsUF);
	$res = ($enID>0);
	if (!$res)
	{
		if($ex =  $APPLICATION->GetException())
		ShowError($ex->messages[0]["text"]);
	}
	
	$arFieldsUF = Array(
			"ENTITY_ID" => "HLBLOCK_".$hlblockID,
			"FIELD_NAME" => "UF_DISCOUNT",
			"USER_TYPE_ID" => "double",
			"XML_ID" => "",
			"SORT" => "100",
			"MULTIPLE" => "",
			"MANDATORY" => "",
			"SHOW_FILTER" => "N",
			"SHOW_IN_LIST" => "",
			"EDIT_IN_LIST" => "",
			"IS_SEARCHABLE" => "",
			"SETTINGS" => Array (
				"DAFAULT_VALUE" => Array ()
	),
			"EDIT_FORM_LABEL" => array(
				"ru" => "Скидка, %",
				"en" => "Discount, %",
	),
			"LIST_COLUMN_LABEL" => array(
				"ru" => "Скидка, %",
				"en" => "Discount, %",
	),
			"LIST_FILTER_LABEL" => array(
				"ru" => "Скидка, %",
				"en" => "Discount, %",
	),
			"ERROR_MESSAGE" => array(
				"ru" => "",
				"en" => "",
	),
			"HELP_MESSAGE" => array(
				"ru" => "",
				"en" => "",
	)
	);
	
	
	$enID = $obUserField->Add($arFieldsUF);
	$res = ($enID>0);
	if (!$res)
	{
		if($ex =  $APPLICATION->GetException())
		ShowError($ex->messages[0]["text"]);
	}	
	
	ShowNote('Поля HighloadBlock созданы!');
}
if(defined('CATALOG_IB') && CATALOG_IB > 0)
{
	$properties = CIBlockProperty::GetList(Array(), Array("IBLOCK_ID"=>CATALOG_IB, 'CODE' => "DISCOUNT"));
	if ($prop_fields = $properties->GetNext())
	{
		ShowNote('Свойство инфоблока "Скидка, %" уже создано!');
	}
	else
	{
		$arFields = Array(
	        "NAME" => "Скидка, %",
	        "ACTIVE" => "Y",
	        "SORT" => "500",
	        "CODE" => "DISCOUNT",
	        "PROPERTY_TYPE" => "S",
	        "IBLOCK_ID" => CATALOG_IB
		);
		
		$ibp = new CIBlockProperty;
		$PropID = $ibp->Add($arFields);
	}
}