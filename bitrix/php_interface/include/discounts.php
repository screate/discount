<?
use Bitrix\Main;
use Bitrix\Main\Entity;

$eventManager = Main\EventManager::getInstance();

$eventManager->addEventHandler('', 'UserDiscountsOnAfterUpdate', '__OnAddUpdateHL');
$eventManager->addEventHandler('', 'UserDiscountsOnAfterAdd', '__OnAddUpdateHL');

AddEventHandler('iblock','OnAfterIblockElementAdd','__OnElementDiscount');
AddEventHandler('iblock','OnAfterIblockElementUpdate','__OnElementDiscount');

AddEventHandler("sale", "OnCondSaleControlBuildList", "__RegisterBuildList", 10001);

function __RegisterBuildList()
{
	CModule::IncludeModule('sale');
	include_once "user_cond.php";
	
	return CSaleCondCtrlUserFields::GetControlDescr();
}

function __recalcSaleDiscount($user_id)
{
	if(!$user_id) return;	
	
	CModule::IncludeModule('sale');
	CModule::IncludeModule('highloadblock');
	
	$UserPrc = false;
	
	$hlblock   = Bitrix\Highloadblock\HighloadBlockTable::getList(array('filter'=>array('NAME' => 'UserDiscounts')))->fetch();
	$entity   = Bitrix\Highloadblock\HighloadBlockTable::compileEntity( $hlblock );
	$entity_data_class = $entity->getDataClass();
	$rsData = $entity_data_class::getList(array(
		"select" => array("*"),
		"order" => array("ID" => "ASC"),
		"filter" => array('UF_USER_ID' => $user_id)
	));
	if($arData = $rsData->Fetch())
	{
		$UserPrc = $arData['UF_DISCOUNT'];
	}
	if($UserPrc)
	{
		$bCreated = false;
		
		$dbSaleDiscounts = CSaleDiscount::GetList(
			array(),
			array(
				"ACTIVE" => "Y",
				//"XML_ID" => 'HL_DISCOUNT_'.$UserPrc,
			),
			false,
			false,
			array('ID','NAME',"XML_ID",'CONDITIONS')
		);
		while ($arSaleDiscount = $dbSaleDiscounts->Fetch())
		{
			$issetUsers = array();
			$CONDITIONS = unserialize($arSaleDiscount['CONDITIONS']);			
			foreach($CONDITIONS['CHILDREN'] as $arChildren)
			{
				if($arChildren['CLASS_ID'] == 'CondSaleOrderUserID')
				{
					$issetUsers[$arChildren['DATA']['value']] = $arChildren['DATA']['value'];
				}
			}
			if($arSaleDiscount['XML_ID'] == 'HL_DISCOUNT_'.$UserPrc)
			{
				$bCreated = true;
				if(!in_array($user_id, $issetUsers))
				{
					$CONDITIONS['CHILDREN'][] = Array(
                            'CLASS_ID' => 'CondSaleOrderUserID',
                            'DATA' => Array(
                                    'logic' => 'Equal',
                                    'value' => $user_id
								)
						);
					
					$arDiscountFields = array(
						'NAME' => $arSaleDiscount['NAME'],
						"ACTIVE" => 'Y',
						'CONDITIONS' => $CONDITIONS
					);
					CSaleDiscount::Update($arSaleDiscount['ID'], $arDiscountFields);
				}
			}
			else
			{
				if(in_array($user_id, $issetUsers))
				{
					
					$tmpCond = array();
					foreach($CONDITIONS['CHILDREN'] as $arChildren)
					{
						if(!($arChildren['CLASS_ID'] == 'CondSaleOrderUserID' && $arChildren['DATA']['value'] == $user_id))
						{
							$tmpCond[] = $arChildren;
						}
					}
					if(empty($tmpCond))
					{
						CSaleDiscount::Delete($arSaleDiscount['ID']);
					}
					else
					{
						$CONDITIONS['CHILDREN'] = $tmpCond;
						$arDiscountFields = array(
							'NAME' => $arSaleDiscount['NAME'],
							"ACTIVE" => 'Y',
							'CONDITIONS' => $CONDITIONS
						);
						CSaleDiscount::Update($arSaleDiscount['ID'], $arDiscountFields);
					}
				}
			}
		}
		
		
		if(!$bCreated)
		{
			$arDiscountFields = array(
				"LID" => 's1',
				"NAME" => 'Скидка пользователям - '.$UserPrc.'%',
				"ACTIVE_FROM" => '',
				"ACTIVE_TO" => '',
				"ACTIVE" => 'Y',
				"SORT" => 100,
				"PRIORITY" => 1,
				"LAST_DISCOUNT" => 'Y',
				"XML_ID" => 'HL_DISCOUNT_'.$UserPrc,
				'CONDITIONS' => Array(
			            'CLASS_ID' => 'CondGroup',
			            'DATA' => Array(
			                    'All' => 'OR',
			                    'True' => 'True'
						),
			            'CHILDREN' => Array(
							Array(
	                            'CLASS_ID' => 'CondSaleOrderUserID',
	                            'DATA' => Array(
	                                    'logic' => 'Equal',
	                                    'value' => $user_id
									)
							),
						)
					),
				'ACTIONS' => array(
					'CLASS_ID' => 'CondGroup',
					'DATA' => Array(
	                    'All' => 'AND',
					),
					'CHILDREN' => Array(
						Array(
                            'CLASS_ID' => 'ActSaleBsktGrp',
                            'DATA' => Array(
								'Type' => 'Discount',
								'Value' => $UserPrc,
								'Unit' => 'Perc',
								'All' => 'AND',
							),
							'CHILDREN' => Array()
						),
					)
				),
				'USER_GROUPS' => array(2),
			);			
			$DiscountID = CSaleDiscount::Add($arDiscountFields);
		}
	}
}

function __OnAddUpdateHL(Entity\Event $event)
{
	$fields = $event->getParameter("fields");	
	$user_id = $fields['UF_USER_ID'];		
	
	__recalcSaleDiscount($user_id);
}

function __OnElementDiscount($arFields)
{
	$ProductPrc = false;
	$PropID = false;

	$res = CIBlockElement::GetProperty($arFields['IBLOCK_ID'],$arFields['ID'], "sort", "asc", array("CODE" => "DISCOUNT", 'EMPTY' => 'N'));
	if ($ob = $res->GetNext())
	{
		$PropID = $ob['ID'];
		$ProductPrc = $ob['VALUE'];
	}

	if($ProductPrc)
	{
		CModule::IncludeModule('catalog');
		$dbProductDiscounts = CCatalogDiscount::GetList(
			array(),
			array(
				"ACTIVE" => "Y",
				"VALUE" => $ProductPrc,
				"XML_ID" => 'IB_PROPERTY_'.$ProductPrc,
			),
			false,
			false,
			array(
				"ID", "VALUE"
			)
		);
		if (!$dbProductDiscounts->Fetch())
		{
			$arDiscountFields = Array(
			    'SITE_ID' => 's1',
			    'ACTIVE' => 'Y',
			    'RENEWAL' => 'N',
			    'XML_ID' => 'IB_PROPERTY_'.$ProductPrc,
			    'NAME' => 'Скидка по свойству - '.$ProductPrc.'%',
			    'SORT' => 100,
			    'VALUE_TYPE' => 'P',
			    'VALUE' => $ProductPrc,
			    'CURRENCY' => 'RUB',
			    'PRIORITY' => 1,
			    'LAST_DISCOUNT' => 'N',
			    'GROUP_IDS' => Array(),
			    'CATALOG_GROUP_IDS' => Array(),			
			    'CONDITIONS' => Array(
			            'CLASS_ID' => 'CondGroup',
			            'DATA' => Array(
			                    'All' => 'AND',
			                    'True' => 'True'
						),
			            'CHILDREN' => Array(
							Array(
	                            'CLASS_ID' => 'CondIBIBlock',
	                            'DATA' => Array(
	                                    'logic' => 'Equal',
	                                    'value' => $arFields['IBLOCK_ID']
										)
							),
							Array(
	                            'CLASS_ID' => 'CondIBProp:'.$arFields['IBLOCK_ID'].':'.$PropID,
	                            'DATA' => Array(
	                                    'logic' => 'Equal',
	                                    'value' => $ProductPrc
										)
							)
						)
					)
				);
			$ID = CCatalogDiscount::Add($arDiscountFields);
		}
	}
}



