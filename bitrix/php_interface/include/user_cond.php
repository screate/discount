<?
/*
 * USER ID SALE CONDITION
 * */

class CSaleCondCtrlUserFields extends CSaleCondCtrlComplex
{
	public static function GetClassName()
	{
		return __CLASS__;
	}

	public static function GetControlShow($arParams)
	{
		$arControls = static::GetControls();
		$arResult = array(
			'controlgroup' => true,
			'group' =>  false,
			'label' => 'Пользовательские',
			'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
			'children' => array()
		);
		foreach ($arControls as &$arOneControl)
		{
			$arResult['children'][] = array(
				'controlId' => $arOneControl['ID'],
				'group' => false,
				'label' => $arOneControl['LABEL'],
				'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
				'control' => array(
				$arOneControl['PREFIX'],
					static::GetLogicAtom($arOneControl['LOGIC']),
					static::GetValueAtom($arOneControl['JS_VALUE'])
				)
			);
		}
		if (isset($arOneControl))
		unset($arOneControl);

		return $arResult;
	}

	public static function Generate($arOneCondition, $arParams, $arControl, $arSubs = false)
	{
		$strResult = '';

		if (is_string($arControl))
		{
			$arControl = static::GetControls($arControl);
		}
		$boolError = !is_array($arControl);

		if (!$boolError)
		{
			$arValues = static::Check($arOneCondition, $arOneCondition, $arControl, false);
			$boolError = ($arValues === false);
		}

		if (!$boolError)
		{
			$arLogic = static::SearchLogic($arValues['logic'], $arControl['LOGIC']);
			if (!isset($arLogic['OP'][$arControl['MULTIPLE']]) || empty($arLogic['OP'][$arControl['MULTIPLE']]))
			{
				$boolError = true;
			}
			else
			{
				$multyField = is_array($arControl['FIELD']);
				$issetField = '';
				$valueField = '';
				if ($multyField)
				{
					$fieldsList = array();
					foreach ($arControl['FIELD'] as &$oneField)
					{
						$fieldsList[] = $arParams['ORDER'].'[\''.$oneField.'\']';
					}
					unset($oneField);
					$issetField = implode(') && isset (', $fieldsList);
					$valueField = implode('*',$fieldsList);
					unset($fieldsList);
				}
				else
				{
					$issetField = $arParams['ORDER'].'[\''.$arControl['FIELD'].'\']';
					$valueField = $issetField;
				}
				switch ($arControl['FIELD_TYPE'])
				{
					case 'int':
					case 'double':
						$strResult = str_replace(array('#FIELD#', '#VALUE#'), array($valueField, $arValues['value']), $arLogic['OP'][$arControl['MULTIPLE']]);
						break;
					case 'char':
					case 'string':
					case 'text':
						$strResult = str_replace(array('#FIELD#', '#VALUE#'), array($valueField, '"'.EscapePHPString($arValues['value']).'"'), $arLogic['OP'][$arControl['MULTIPLE']]);
						break;
					case 'date':
					case 'datetime':
						$strResult = str_replace(array('#FIELD#', '#VALUE#'), array($valueField, $arValues['value']), $arLogic['OP'][$arControl['MULTIPLE']]);
						break;
				}
				$strResult = 'isset('.$issetField.') && '.$strResult;
			}
		}

		return (!$boolError ? $strResult : false);
	}

	/**
	 * @param bool|string $strControlID
	 * @return array|bool
	 */
	public static function GetControls($strControlID = false)
	{
		$arControlList = array(
			'CondSaleOrderUserID' => array(
				'ID' => 'CondSaleOrderUserID',
				'EXECUTE_MODULE' => 'sale',
				'MODULE_ID' => false,
				'MODULE_ENTITY' => '',
				'ENTITY' => 'ORDER',
				'FIELD' => 'USER_ID',
				'FIELD_TYPE' => 'int',
				'MULTIPLE' => 'N',
				'GROUP' => 'N',
				'LABEL' => 'ID пользователя',
				'PREFIX' => 'ID пользователя',
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ)),
				'JS_VALUE' => array(
					'type' => 'input',
					'multiple' => '',					
				),				
			)
		);
		if (false === $strControlID)
		{
			return $arControlList;
		}
		elseif (isset($arControlList[$strControlID]))
		{
			return $arControlList[$strControlID];
		}
		else
		{
			return false;
		}
	}
	
	public static function GetShowIn($arControls)
	{
		$arControls = array(CSaleCondCtrlGroup::GetControlID());
		return $arControls;
	}
}


