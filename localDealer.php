			$directionsLink = <<<HTML
<a href="$directionsURL" target="_blank" class="btn dealer-directions-link">Directions</a>
HTML;

            ##################################################
			## Singular Dealer Page
			##################################################
			if ( strlen( $city ) && strlen( $state ) )
			{
				$dealerPageLinkPath = '/local-dealers/location/';

				if ( strlen( $county ) )
				{
					$dealerPageLinkPath .= mod_encodeLocationURLItem( $state ) . '/' . mod_encodeLocationURLItem( $county ) . '/' . mod_encodeLocationURLItem( $city ) . '/';
				}
				else
				{
					$dealerPageLinkPath .= mod_encodeLocationURLItem( $state ) . '/' . mod_encodeLocationURLItem( $city ) . '/';
				}


				$dealerPageLinkPath .= 'dealer' . '/' . $company_id . '/' . mod_encodeLocationURLItem( $company );
			}

			if ( strlen( $dealerPageLinkPath ) )
			{
				$dealerPageLinkPath = str_replace('//', '/', $dealerPageLinkPath );
				if ( $type === 'zip-list' )
				{
					$dealerPageLink = <<<HTML
					<a class="individual-dealer-page-link btn more-info-btn" href="$dealerPageLinkPath">MORE INFO</a>
HTML;
				}
				else if ( $type === 'location-list' )
				{
					$dealerPageLink = <<<HTML
	<a class="btn individual-dealer-page-link btn more-info-btn" href="$dealerPageLinkPath">MORE INFO</a>
HTML;
				}
			}

if ( $type === 'location-list' )
			{
				$dealerHTML = <<<HTML
<div class="dealer-wrapper" data-address-id="$address_id">
<div class="dealer-name">$company</div>
$mapInfoHTML
<div class="dealer-buttons">$directionsLink$dealerPageLink</div>
</div>
HTML;
			}
			else
			{
				$dealerHTML = <<<HTML
<div class="dealer-wrapper" data-address-id="$address_id">
	<div class="dealer">
		<div class="dealer-name">
			$company
		</div>
		$mapInfoHTML
		<div class="dealer-buttons">
			$directionsLink$dealerPageLink
		</div>
	</div>
</div>
HTML;
			}






















<?
	##################################################
	## Category Sorting & Promotions
	##################################################
	$itemCategoryIDList = array();
	$itemCategoryCount = 0;
	$criteoPartNumber = array();

	
	if ( !$categoryCouponCampaignIndex ){
		$categoryCouponCampaignIndex = array();
	}
	if($tileGroupClass == 'category-tiles')
	{
		foreach($categoryList as $i => $category)
		{
			$itemCount = $category['itemCount'] ?: 0;			
			if($itemCount > 0)
			{
				$itemCategoryIDList[] = $category['id'];
				$categoryList[$i]['priceRange'] = mod_getCategoryPriceRange(array('CategoryID'=>$category['id']));

			}
		}
		$criteoList[] = $ePIM->p->epimProductList(array('CategoryID' => $itemCategoryIDList, 'Start' => 0, 'Limit' => 1, 'PartNumber' => 1));
		$criteoPartNumber[] = $criteoList[$i][0]['PartNumber'];
		$itemCategoryCount = sizeof($itemCategoryIDList);
		$criteoPartNumber_json = json_encode($criteoPartNumber);
		//$criteoPartNumber_json = array_slice($criteoPartNumber_json, 0, 3);
		//$criteoPartNumber_json = str_replace('"','', (string) $criteoPartNumber_json);
		jLog('list IDs: ',  $itemCategoryIDList);
		jLog('prod list: ', $criteoList);
		jLog('part num: ', $criteoPartNumber);
		
		$criteoListingTag = <<<HTML
			<script type="text/javascript">
				var criteoPartNumber_json = $criteoPartNumber_json;;
					dataLayer.push({
						'ecomm_prodid': 'criteoPartNumber_json;',
					});
			</script>
HTML;
		
		##################################################
		## Category Sorting & Promotions
		##################################################
?>




<?php
#####################################################
## PHP
#####################################################
use Utility as u;
header('Content-Type: text/html; charset=utf-8');

global $core, $myModules;
$Content = $myModules->modules['Content'];

$expressCheckout = getSafeFormValue('expressCheckout');
$useFormDictionary = (bool) $this->configure['useFormDictionary'];
$inventoryControl = $this->inventoryControl();

$params = is_array($params) ? $params : [];
$params['refreshInventory'] = (int) $inventoryControl;
$cart = $this->buildCart($params);
$itemList = $cart['itemList'];
$quoteList = $cart['quoteList'];

$viewCartCustomMessage = $useFormDictionary ? trim($Content->dictionary['eICP_cart_custom_bottom']) : trim($this->configure['viewCartCustomMessage']);
if($viewCartCustomMessage)
{
	$viewCartCustomMessage = <<<HTML
<div class="eicpCartCustomMessage">
$viewCartCustomMessage
</div>
HTML;
}

if(function_exists('mod_eicpContinueShoppingURL'))
{
	$continueShopping = mod_eicpContinueShoppingURL();
}
else
{
	$continueShopping = trim($this->configure['continueShopping']);

	if(strlen($continueShopping) && !preg_match('/^https?:\/\//',$continueShopping))
	{
		$continueShopping = $Content->buildURL(['url' => $continueShopping]);
	}
}

if(!$continueShopping)
{
	$continueShopping = $Content->buildURL();
}

$continueShoppingBack = $this->configure['continueShoppingBack'];
$cartItemsNonTabular = (bool) $this->configure['cartItemsNonTabular'];
$cartItemShowPartNumberLabel = (bool) $this->configure['cartItemShowPartNumberLabel'];
$cartItemShowPriceQuantityLabels = (bool) $this->configure['cartItemShowPriceQuantityLabels'];
$cartCombinePartNumberDescription = (bool) $this->configure['cartCombinePartNumberDescription'];
$cartDescriptionBeforePartNumber = (bool) $this->configure['cartDescriptionBeforePartNumber'];
$cartItemShowOriginalPrice = (bool) $this->configure['cartItemShowOriginalPrice'];
$leadTimeDisplay = strtolower(trim($this->configure['leadTimeDisplay']));
$showCartItemCommentField = (bool) $this->configure['showCartItemCommentField'];

$cartItemImageWidth = (int) $this->configure['cartItemImageWidth'];
$cartItemImageHeight = (int) $this->configure['cartItemImageHeight'];
$cartItemImageQuality = (int) $this->configure['cartItemImageQuality'] ?: 80;
$cartItemImageQuality = max(min($cartItemImageQuality, 100), 1);
$cartItemImageCrop = (bool) $this->configure['cartItemImageCrop'];

$epimUploadDir = "/{$this->siteInformation['uploadDir']}/ePIM/";
$epimUploadDirRegex = '/^'.preg_quote($epimUploadDir, '/').'.+?\//';
$epimOriginalDir = "{$epimUploadDir}original/";

if($useFormDictionary)
{
	$itemPriceBypassDictionaryKey = $this->configure['itemPriceBypassDictionaryKey'] ?: 'eICP_cart_item_price_bypass';
	$itemPriceBypassDisplay = trim($Content->dictionary[$itemPriceBypassDictionaryKey]) ?: '---';

	$removeButtonText = $Content->dictionary['eICP_cart_item_remove_button'];
	$removeHeaderText = $Content->dictionary['eICP_cart_item_remove'];

	$checkoutButtonText = $Content->dictionary['eICP_cart_checkout_button'];

	if(sizeof($itemList) && sizeof($quoteList))
	{
		$checkoutButtonText = $Content->dictionary['eICP_cart_checkout_quote_button'] ?: "{$Content->dictionary['eICP_cart_checkout_button']} / {$Content->dictionary['eICP_cart_quote_button']}";
	}
	elseif(sizeof($quoteList))
	{
		$checkoutButtonText = $Content->dictionary['eICP_cart_quote_button'];
	}

	$breadButtonText = $Content->dictionary['eICP_cart_bread_button'];
	$cancelButtonText = $Content->dictionary['eICP_form_cancel'];
}
else
{
	$itemPriceBypassDisplay = trim($this->configure['itemPriceBypassDisplay']) ?: '---';

	$removeButtonText = strlen($this->configure['cartItemRemoveButtonText']) ? $this->configure['cartItemRemoveButtonText'] : 'X';
	$removeHeaderText = strlen($this->configure['cartItemRemoveHeaderText']) ? $this->configure['cartItemRemoveHeaderText'] : 'Remove';

	$checkoutButtonText = 'Checkout';

	if(sizeof($itemList) && sizeof($quoteList))
	{
		$checkoutButtonText = 'Checkout / Request Quote';
	}
	elseif(sizeof($quoteList))
	{
		$checkoutButtonText = 'Request Quote';
	}

	$breadButtonText = 'Pay over Time: Pre-Qualify';
	$cancelButtonText = 'Cancel';
}

$cancelButtonTextJSSafe = json_encode($cancelButtonText);

if($expressCheckout)
{
	$removeButtonClass = '';
	$removeItemFunction = <<<JAVASCRIPT
cart_item_id = cart_item_id || null;

if (cart_item_id) {
	eICP.cart.cacheOldCartData();
	eICP.getPage('&command=eicpRemoveCartItem&cart_item_id='+cart_item_id, '', eICP.checkout.itemList);
}
JAVASCRIPT;
}
else
{
	if($useFormDictionary)
	{
		$removeItemConfirmButton = json_encode($Content->dictionary['eICP_cart_item_remove']);
		$removeItemConfirmMessage = json_encode($Content->dictionary['eICP_cart_item_remove_confirm']);
		$removeItemConfirmMessage = preg_replace_callback('/\{\{(.+)\}\}/', function($matches) {
			$generic = json_encode($matches[1]);
			return <<<JAVASCRIPT
" + (cart_item_name ? '"' + cart_item_name + '"' : {$generic}) + "
JAVASCRIPT;
		}, $removeItemConfirmMessage);
	}
	else
	{
		$removeItemConfirmButton = json_encode('Remove');
		$removeItemConfirmMessage = <<<JAVASCRIPT
'Are you sure you want to remove ' + (cart_item_name ? '"' + cart_item_name + '"' : 'this item') + ' from your cart?'
JAVASCRIPT;
	}

	$removeButtonClass = 'eicpPopupTrigger';
	$removeItemFunction = <<<JAVASCRIPT
cart_item_id = cart_item_id || null;
cart_item_name = cart_item_name || '';

if (cart_item_id) {
	var alertParams = {};
	alertParams.message = $removeItemConfirmMessage;
	alertParams.messageClass = 'eicpCartItemRemoveConfirmationMessage';
	alertParams.buttons = [];
	alertParams.buttons.push({
		label: $removeItemConfirmButton,
		className: 'eicpCartItemConfirmRemoveButton',
		click: function() {
			eICP.popup.close();
			eICP.loader.set('eICPCheckout');
			eICP.cart.cacheOldCartData();
			eICP.getPage('&command=eicpRemoveCartItem&cart_item_id='+cart_item_id,'',eICP.checkout.itemList);
		}
	});
	alertParams.buttons.push({
		label: $cancelButtonTextJSSafe,
		className: 'eicpCartItemCancelRemoveButton',
		click: eICP.popup.close
	});
	alertParams.buttonsClass = 'eicpCartItemRemoveConfirmationButtons';
	eICP.popup.alert(alertParams);
}
JAVASCRIPT;
}

$shopatron = $this->configure['SHOPATRON'];
$checkoutMemberLogin = trim(strtolower($this->configure['checkoutMemberLogin']));
$checkoutFunction = 'eICP.checkout.address()';
$showMemberLogin = false;

if(!$quoteList && $shopatron)
{
	$checkoutFunction = 'eICP.checkout.shopatron()';
}
elseif($checkoutMemberLogin == 'require' || ($checkoutMemberLogin == 'prompt' && !$_SESSION['eICP_guest_checkout']))
{
	if(array_key_exists('Member', $myModules->modules))
	{
		$Member = $myModules->modules['Member'];
		$member_id = $Member->member_id ?: $Member->memberGetMyMemberID($_SESSION['member_sid']);

		if(!$member_id)
		{
			$checkoutFunction = 'eICP.checkout.memberLogin()';
			$showMemberLogin = true;
		}
	}
}

$emptyCartButtonText = $useFormDictionary ? $Content->dictionary['eICP_cart_empty_button'] : 'Empty Cart';
$emptyCartButtonTextEscaped = addslashes($emptyCartButtonText);
$emptyCartButton = <<<HTML
<button type="button" id="eicpEmptyCart" class="eicpButton eicpEmptyCartButton eicpPopupTrigger" onclick="eICP.cart.emptyCart()">$emptyCartButtonText</button>
HTML;

$emptyCartConfirmationText = $useFormDictionary ? $Content->dictionary['eICP_cart_empty_confirm'] : 'Are you sure you want to remove all items from your cart?';
$emptyCartConfirmationText = addslashes($emptyCartConfirmationText);

$continueShoppingButtonText = $useFormDictionary ? $Content->dictionary['eICP_cart_continue_shopping'] : 'Continue Shopping';
$continueShoppingFunction = $continueShoppingBack == 1 ? "window.history.back()" : "document.location.href='$continueShopping'";
$continueShoppingButton = <<<HTML
<button type="button" class="eicpButton eicpContinueShopping" onclick="$continueShoppingFunction">$continueShoppingButtonText</button>
HTML;

$checkoutButton = <<<HTML
<button type="button" class="eicpButton eicpCheckout" id="eicpCheckoutButton" onclick="$checkoutFunction">$checkoutButtonText</button>
HTML;

$params = array();

if($expressCheckout)
{
	$continueShoppingButton = '';
	$checkoutButton = <<<HTML
<button type="button" name="eicpContinue" id="eicpContinue" class="eicpButton eicpContinue" onclick="eICP.expressCheckout.payment()">$checkoutButtonText</button>
HTML;
}

#####################################################
## Bolt Checkout Button
#####################################################
if($this->bolt_publish_key)
{
	$checkoutButton = <<<HTML
<div id="eicpBoltCheckoutButton" class="eicpBoltCheckoutButton bolt-checkout-button"></div>
HTML;
}

#####################################################
## Bread Pre-Qualify Button
#####################################################
$enableBread = ($this->configure['BREAD'] && $this->configure['BREAD_API_KEY'] && $cart['itemList']);
$breadOptions = null;

if($enableBread)
{
	$breadOptions = $this->buildBreadData(['preQualify' => 1]);
	$checkoutButton = <<<HTML
<button type="button" class="eicpButton eicpBreadButton" id="eicpCartBreadButton" onclick="eICP.cart.breadPreQualify()">$breadButtonText</button>
$checkoutButton
HTML;
}

#####################################################
## Cart Item Field Labels
#####################################################
if($useFormDictionary)
{
	$cartItemImageHeader = $Content->dictionary['eICP_cart_item_image'];
	$cartItemPartNumberLabelText = $Content->dictionary['eICP_cart_item_part_number'];
	$cartItemNameLabelText = $Content->dictionary['eICP_cart_item_name'];
	$cartItemCommentLabelText = $Content->dictionary['eICP_cart_item_comment'];
	$cartItemPriceLabelText = $Content->dictionary['eICP_cart_item_price'];
	$cartItemQuantityLabelText = $Content->dictionary['eICP_cart_item_quantity'];
	$cartItemTotalLabelText = $Content->dictionary['eICP_cart_item_total'];
}
else
{
	$cartItemImageHeader = trim($this->configure['cartItemImageHeader']);
	$cartItemPartNumberLabelText = trim($this->configure['cartItemPartNumberLabelText']) ?: 'Part Number';
	$cartItemNameLabelText = trim($this->configure['cartItemNameLabelText']) ?: 'Description';
	$cartItemCommentLabelText = trim($this->configure['cartItemCommentLabelText']) ?: 'Comments';
	$cartItemPriceLabelText = trim($this->configure['cartItemPriceLabelText']) ?: 'Item Price';
	$cartItemQuantityLabelText = trim($this->configure['cartItemQuantityLabelText']) ?: 'Quantity';
	$cartItemTotalLabelText = trim($this->configure['cartItemTotalLabelText']) ?: 'Total Price';
}

$cartItemCommentLabelText = htmlentities($cartItemCommentLabelText);
$itemTotalDisplaySelector = '.eicpCartItemField.eicpCartItemTotal';

if($cartItemsNonTabular == 1)
{
	$itemTotalDisplaySelector = '.eicpCartItemField.eicpCartItemPriceQuantity .eicpCartItemTotal';

	if($cartItemShowPriceQuantityLabels == 1)
	{
		$itemTotalDisplaySelector .= ' > .eicpValue';
	}
}

#####################################################
## Cart Items
#####################################################
$itemListContent = '';
$rowClass = 'eicpGrey2';

foreach($itemList as $item)
{
	$rowClass = $rowClass == 'eicpGrey2' ? 'eicpGrey1' : 'eicpGrey2';

	$cart_item_id = $item['cart_item_id'];
	$cart_item_part_number = $item['cart_item_part_number'];
	$cart_item_part_number_display = $cart_item_part_number;
	$cart_item_name = trim($item['cart_item_name']);
	$cart_item_name_safe = htmlentities($cart_item_name);
	$cart_item_name_safe = addslashes($cart_item_name_safe);
	$cart_item_comment = trim($item['cart_item_comment']);
	$cart_item_quantity = (int) $item['cart_item_quantity'];
	$cart_item_image = $item['cart_item_image'];
	$cart_item_price = $item['cart_item_price'];
	$cart_item_price_bypass = $item['cart_item_price_bypass'];
	$cart_item_total = $cart_item_price * $cart_item_quantity;

	$cart_item_price = money_format("%.2n",$cart_item_price);
	$cart_item_total = money_format("%.2n",$cart_item_total);
	$quantityField = <<<HTML
<input type="text" class="eicpInput eicpQuantityInput" name="cart_item_quantity[$cart_item_id]" value="$cart_item_quantity" size="2" />
HTML;

	if($cart_item_price_bypass)
	{
		$cart_item_price = '---';
		$cart_item_total = $itemPriceBypassDisplay;
	}

	if($showCartItemCommentField)
	{
		$cart_item_comment = htmlentities($cart_item_comment);
		$cart_item_comment = <<<HTML
<textarea rows="2" cols="30" name="cart_item_comment[$cart_item_id]" class="eicpInput eicpTextArea eicpCommentInput" placeholder="$cartItemCommentLabelText">$cart_item_comment</textarea>
HTML;
	}
	else
	{
		$cart_item_comment = nl2br($cart_item_comment);
	}

	if(strlen($cart_item_comment))
	{
		$cart_item_comment = <<<HTML
<div class="eicpCartItemComment">$cart_item_comment</div>
HTML;
	}

	$propertyList = $item['propertyList'];
	$descriptionContent = '';
	$propertyListContent = '';

	foreach($propertyList as $property)
	{
		switch($property['cart_item_property_key'])
		{
			case 'CART_LEAD_TIME':
			case 'CART_INVENTORY_LOCATION_ID':
			case 'CART_TRACK_INVENTORY':
				continue 2;
				break;

			case 'CART_DESCRIPTION':
				$cart_item_description = trim($property['cart_item_property_value']);
				$cart_item_description = json_decode($cart_item_description) ?: $cart_item_description;

				if(is_array($cart_item_description))
				{
					if(sizeof($cart_item_description) == 1)
					{
						$cart_item_description = array_shift($cart_item_description);
					}
					else
					{
						$cart_item_description = array_values($cart_item_description);

						foreach($cart_item_description as $i => $description)
						{
							$descriptionClass = 'eicpCartItemSubTitle eicpCartItemSubTitle'.($i + 1);
							$descriptionContent .= <<<HTML
<div class="$descriptionClass">$description</div>
HTML;
						}
					}
				}

				if(is_string($cart_item_description))
				{
					$descriptionContent .= <<<HTML
<div class="eicpCartItemSubTitle">$cart_item_description</div>
HTML;
				}
				break;

			case 'CART_ORIGINAL_PRICE':
				if((float) $property['cart_item_property_value'] > 0 && $cartItemShowOriginalPrice == 1 && !$cart_item_price_bypass)
				{
					$cart_item_original_price = money_format("%.2n",$property['cart_item_property_value']);

					if($cart_item_original_price != $cart_item_price)
					{
						$cart_item_price = <<<HTML
<span class="eicpOriginalPrice">$cart_item_original_price</span><span class="eicpPrice">$cart_item_price</span>
HTML;
					}
				}
				break;

			default:
				if($property['cart_item_property_display'])
				{
					$propertyListContent .= <<<HTML
<li class="eicpCartItemProperty"><span class="eicpCartItemPropertyTitle">{$property['cart_item_property_title']}</span><span class="eicpCartItemPropertyValue">{$property['cart_item_property_value']}</span></li>
HTML;
				}
		}
	}

	if($propertyListContent)
	{
		$propertyListContent = <<<HTML
<ul class="eicpCartItemPropertyList">
$propertyListContent
</ul>
HTML;
	}

	if($inventoryControl)
	{
		$inventoryContent = $this->inventoryDisplay($item);

		if($inventoryContent)
		{
			$cart_item_comment .= <<<HTML
<div class="eicpCartInventoryMessage">$inventoryContent</div>
HTML;
		}
	}

	if($leadTimeDisplay == 'peritem')
	{
		$leadTimeContent = $this->leadTimeDisplay([
			'cart_item' => $item,
			'display_type' => $leadTimeDisplay
		]);

		if($leadTimeContent)
		{
			$cart_item_comment .= <<<HTML
<div class="eicpCartItemLeadTime">$leadTimeContent</div>
HTML;
		}
	}

	$imageContent = '';
	if($cart_item_image && file_exists(str_replace('//', '/', $this->siteInformation['siteBaseDir'].$cart_item_image)))
	{
		if($cartItemImageWidth || $cartItemImageHeight)
		{
			$imageParams = array();
			$imageParams['image'] = preg_replace($epimUploadDirRegex, $epimOriginalDir, $cart_item_image);
			if($cartItemImageWidth)
			{
				$imageParams['width'] = $cartItemImageWidth;
			}
			if($cartItemImageHeight)
			{
				$imageParams['height'] = $cartItemImageHeight;
			}
			if($cartItemImageWidth && $cartItemImageHeight && $cartItemImageCrop)
			{
				$imageParams['crop'] = 1;
			}
			$imageParams['quality'] = $cartItemImageQuality;
			$cart_item_image = $core->getImage($imageParams);
		}
		$imageAlt = trim($cart_item_name) ?: trim($cart_item_part_number);
		$imageAlt = htmlentities($imageAlt);
		$imageContent .= <<<HTML
<img class="eicpProductImage" src="$cart_item_image" alt="$imageAlt"></img>
HTML;
	}

	if($cartItemShowPartNumberLabel == 1)
	{
		$cart_item_part_number_display = <<<HTML
<label class="eicpLabel">$cartItemPartNumberLabelText</label><span class="eicpValue">$cart_item_part_number</span>
HTML;
	}
	if($cartItemShowPriceQuantityLabels == 1)
	{
		$cart_item_price = <<<HTML
<label class="eicpLabel">$cartItemPriceLabelText</label><span class="eicpValue">$cart_item_price</span>
HTML;
		$quantityField = <<<HTML
<label class="eicpLabel">$cartItemQuantityLabelText</label><span class="eicpValue">$quantityField</span>
HTML;
		$cart_item_total = <<<HTML
<label class="eicpLabel">$cartItemTotalLabelText</label><span class="eicpValue">$cart_item_total</span>
HTML;
	}

	if($cartItemsNonTabular == 1)
	{
		$itemListContent .= <<<HTML
<div class="$rowClass eicpCartItem">
<div class="eicpCartItemField eicpCartItemImage">$imageContent</div>
<div class="eicpCartItemField eicpCartItemInfo">
HTML;
		if($cartDescriptionBeforePartNumber == 1)
		{
			$itemListContent .= <<<HTML
<div class="eicpCartItemDescription">
<div class="eicpCartItemName">$cart_item_name</div>
$descriptionContent
$propertyListContent
</div>
<div class="eicpCartItemPartNumber">$cart_item_part_number_display</div>
HTML;
		}
		else
		{
			$itemListContent .= <<<HTML
<div class="eicpCartItemPartNumber">$cart_item_part_number_display</div>
<div class="eicpCartItemDescription">
<div class="eicpCartItemName">$cart_item_name</div>
$descriptionContent
$propertyListContent
</div>
HTML;
		}
		$itemListContent .= <<<HTML
$cart_item_comment
{$item['customContent']}
</div>
<div class="eicpCartItemField eicpCartItemPriceQuantity">
<div class="eicpCartItemPriceQuantityTotal">
<div class="eicpCartItemPrice eicpPriceField">$cart_item_price</div>
<div class="eicpCartItemQuantity">$quantityField</div>
<div class="eicpCartItemTotal eicpPriceField">$cart_item_total</div>
</div>
<div class="eicpCartItemRemove"><button type="button" class="eicpButton eicpRemoveButton $removeButtonClass" onclick="eICP.cart.removeItem('$cart_item_id','$cart_item_name_safe')">$removeButtonText</button></div>
</div>
<div class="eicpClear"></div>
</div>
HTML;
	}
	else
	{
		$itemListContent .= <<<HTML
<div class="$rowClass eicpCartItem">
<div class="eicpCartItemField eicpCartItemImage">$imageContent</div>
HTML;
		if($cartCombinePartNumberDescription == 1)
		{
			$itemListContent .= <<<HTML
<div class="eicpCartItemField eicpCartItemInfo">
HTML;
			if($cartDescriptionBeforePartNumber == 1)
			{
				$itemListContent .= <<<HTML
<div class="eicpCartItemDescription">
<div class="eicpCartItemName">$cart_item_name</div>
$descriptionContent
$propertyListContent
</div>
<div class="eicpCartItemPartNumber">$cart_item_part_number_display</div>
HTML;
			}
			else
			{
				$itemListContent .= <<<HTML
<div class="eicpCartItemPartNumber">$cart_item_part_number_display</div>
<div class="eicpCartItemDescription">
<div class="eicpCartItemName">$cart_item_name</div>
$descriptionContent
$propertyListContent
</div>
HTML;
			}
			$itemListContent .= <<<HTML
$cart_item_comment
{$item['customContent']}
</div>
HTML;
		}
		else
		{
			if($cartDescriptionBeforePartNumber == 1)
			{
				$itemListContent .= <<<HTML
<div class="eicpCartItemField eicpCartItemDescription">
<div class="eicpCartItemName">$cart_item_name</div>
$descriptionContent
$propertyListContent
$cart_item_comment
{$item['customContent']}
</div>
<div class="eicpCartItemField eicpCartItemPartNumber">$cart_item_part_number_display</div>
HTML;
			}
			else
			{
				$itemListContent .= <<<HTML
<div class="eicpCartItemField eicpCartItemPartNumber">$cart_item_part_number_display</div>
<div class="eicpCartItemField eicpCartItemDescription">
<div class="eicpCartItemName">$cart_item_name</div>
$descriptionContent
$propertyListContent
$cart_item_comment
{$item['customContent']}
</div>
HTML;
			}
		}
		$itemListContent .= <<<HTML
<div class="eicpCartItemField eicpCartItemPrice eicpPriceField">$cart_item_price</div>
<div class="eicpCartItemField eicpCartItemQuantity">$quantityField</div>
<div class="eicpCartItemField eicpCartItemTotal eicpPriceField">$cart_item_total</div>
<div class="eicpCartItemField eicpCartItemRemove"><button type="button" class="eicpButton eicpRemoveButton $removeButtonClass" onclick="eICP.cart.removeItem('$cart_item_id','$cart_item_name_safe')">$removeButtonText</button></div>
<div class="eicpClear"></div>
</div>
HTML;
	}
}

#####################################################
## Quote Items
#####################################################
$quoteListContent = '';
$rowClass = 'eicpGrey2';

foreach($quoteList as $item)
{
	$rowClass = $rowClass == 'eicpGrey2' ? 'eicpGrey1' : 'eicpGrey2';

	$cart_item_id = $item['cart_item_id'];
	$cart_item_part_number = $item['cart_item_part_number'];
	$cart_item_part_number_display = $cart_item_part_number;
	$cart_item_name = $item['cart_item_name'];
	$cart_item_name_safe = htmlentities($cart_item_name);
	$cart_item_name_safe = addslashes($cart_item_name_safe);
	$cart_item_quantity = (int) $item['cart_item_quantity'];
	$cart_item_image = $item['cart_item_image'];
	$cart_item_comment = trim($item['cart_item_comment']);

	$quantityField = <<<HTML
<input type="text" class="eicpInput eicpQuantityInput" name="cart_item_quantity[$cart_item_id]" value="$cart_item_quantity" size="2" />
HTML;

	if($showCartItemCommentField)
	{
		$cart_item_comment = htmlentities($cart_item_comment);
		$cart_item_comment = <<<HTML
<textarea rows="2" cols="30" name="cart_item_comment[$cart_item_id]" class="eicpInput eicpTextArea eicpCommentInput" placeholder="$cartItemCommentLabelText">$cart_item_comment</textarea>
HTML;
	}
	else
	{
		$cart_item_comment = nl2br($cart_item_comment);
	}

	if(strlen($cart_item_comment))
	{
		$cart_item_comment = <<<HTML
<div class="eicpCartItemComment">$cart_item_comment</div>
HTML;
	}

	$propertyList = $item['propertyList'];
	$descriptionContent = '';
	$propertyListContent = '';

	foreach($propertyList as $property)
	{
		switch($property['cart_item_property_key'])
		{
			case 'CART_LEAD_TIME':
			case 'CART_INVENTORY_LOCATION_ID':
			case 'CART_TRACK_INVENTORY':
			case 'CART_ORIGINAL_PRICE':
				continue 2;
				break;

			case 'CART_DESCRIPTION':
				$cart_item_description = trim($property['cart_item_property_value']);
				$cart_item_description = json_decode($cart_item_description) ?: $cart_item_description;

				if(is_array($cart_item_description))
				{
					if(sizeof($cart_item_description) == 1)
					{
						$cart_item_description = array_shift($cart_item_description);
					}
					else
					{
						$cart_item_description = array_values($cart_item_description);

						foreach($cart_item_description as $i => $description)
						{
							$descriptionClass = 'eicpCartItemSubTitle eicpCartItemSubTitle'.($i + 1);
							$descriptionContent .= <<<HTML
<div class="$descriptionClass">$description</div>
HTML;
						}
					}
				}

				if(is_string($cart_item_description))
				{
					$descriptionContent .= <<<HTML
<div class="eicpCartItemSubTitle">$cart_item_description</div>
HTML;
				}
				break;

			default:
				if($property['cart_item_property_display'])
				{
					$propertyListContent .= <<<HTML
<li class="eicpCartItemProperty"><span class="eicpCartItemPropertyTitle">{$property['cart_item_property_title']}</span><span class="eicpCartItemPropertyValue">{$property['cart_item_property_value']}</span></li>
HTML;
				}
		}
	}

	if($propertyListContent)
	{
		$propertyListContent = <<<HTML
<ul class="eicpCartItemPropertyList">
$propertyListContent
</ul>
HTML;
	}

	if($inventoryControl)
	{
		$inventoryContent = $this->inventoryDisplay($item);

		if($inventoryContent)
		{
			$cart_item_comment .= <<<HTML
<div class="eicpCartInventoryMessage">$inventoryContent</div>
HTML;
		}
	}

	if($leadTimeDisplay == 'peritem')
	{
		$leadTimeContent = $this->leadTimeDisplay([
			'cart_item' => $item,
			'display_type' => $leadTimeDisplay
		]);

		if($leadTimeContent)
		{
			$cart_item_comment .= <<<HTML
<div class="eicpCartItemLeadTime">$leadTimeContent</div>
HTML;
		}
	}

	$imageContent = '';
	if($cart_item_image && file_exists(str_replace('//', '/', $this->siteInformation['siteBaseDir'].$cart_item_image)))
	{
		if($cartItemImageWidth || $cartItemImageHeight)
		{
			$imageParams = array();
			$imageParams['image'] = preg_replace($epimUploadDirRegex, $epimOriginalDir, $cart_item_image);
			if($cartItemImageWidth)
			{
				$imageParams['width'] = $cartItemImageWidth;
			}
			if($cartItemImageHeight)
			{
				$imageParams['height'] = $cartItemImageHeight;
			}
			if($cartItemImageWidth && $cartItemImageHeight && $cartItemImageCrop)
			{
				$imageParams['crop'] = 1;
			}
			$imageParams['quality'] = $cartItemImageQuality;
			$cart_item_image = $core->getImage($imageParams);
		}
		$imageAlt = trim($cart_item_name) ?: trim($cart_item_part_number);
		$imageAlt = htmlentities($imageAlt);
		$imageContent .= <<<HTML
<img class="eicpProductImage" src="$cart_item_image" alt="$cart_item_part_number"></img>
HTML;
	}

	if($cartItemShowPartNumberLabel == 1)
	{
		$cart_item_part_number_display = <<<HTML
<label class="eicpLabel">$cartItemPartNumberLabelText</label><span class="eicpValue">$cart_item_part_number</span>
HTML;
	}
	if($cartItemShowPriceQuantityLabels == 1)
	{
		$quantityField = <<<HTML
<label class="eicpLabel">$cartItemQuantityLabelText</label><span class="eicpValue">$quantityField</span>
HTML;
	}

	if($cartItemsNonTabular == 1)
	{
		$quoteListContent .= <<<HTML
<div class="$rowClass eicpCartItem">
<div class="eicpCartItemField eicpCartItemImage">$imageContent</div>
<div class="eicpCartItemField eicpCartItemInfo">
HTML;
		if($cartDescriptionBeforePartNumber == 1)
		{
			$quoteListContent .= <<<HTML
<div class="eicpCartItemDescription">
<div class="eicpCartItemName">$cart_item_name</div>
$descriptionContent
$propertyListContent
</div>
<div class="eicpCartItemPartNumber">$cart_item_part_number_display</div>
HTML;
		}
		else
		{
			$quoteListContent .= <<<HTML
<div class="eicpCartItemPartNumber">$cart_item_part_number_display</div>
<div class="eicpCartItemDescription">
<div class="eicpCartItemName">$cart_item_name</div>
$descriptionContent
$propertyListContent
</div>
HTML;
		}
		$quoteListContent .= <<<HTML
$cart_item_comment
{$item['customContent']}
</div>
<div class="eicpCartItemField eicpCartItemPriceQuantity">
<div class="eicpCartItemPriceQuantityTotal">
<div class="eicpCartItemQuantity">$quantityField</div>
</div>
<div class="eicpCartItemRemove"><button type="button" class="eicpButton eicpRemoveButton $removeButtonClass" onclick="eICP.cart.removeItem('$cart_item_id','$cart_item_name_safe')">$removeButtonText</button></div>
</div>
<div class="eicpClear"></div>
</div>
HTML;
	}
	else
	{
		$quoteListContent .= <<<HTML
<div class="$rowClass eicpCartItem">
<div class="eicpCartItemField eicpCartItemImage">$imageContent</div>
HTML;
		if($cartCombinePartNumberDescription == 1)
		{
			$quoteListContent .= <<<HTML
<div class="eicpCartItemField eicpCartItemInfo">
HTML;
			if($cartDescriptionBeforePartNumber == 1)
			{
				$quoteListContent .= <<<HTML
<div class="eicpCartItemDescription">
<div class="eicpCartItemName">$cart_item_name</div>
$descriptionContent
$propertyListContent
</div>
<div class="eicpCartItemPartNumber">$cart_item_part_number_display</div>
HTML;
			}
			else
			{
				$quoteListContent .= <<<HTML
<div class="eicpCartItemPartNumber">$cart_item_part_number_display</div>
<div class="eicpCartItemDescription">
<div class="eicpCartItemName">$cart_item_name</div>
$descriptionContent
$propertyListContent
</div>
HTML;
			}
			$quoteListContent .= <<<HTML
$cart_item_comment
{$item['customContent']}
</div>
HTML;
		}
		else
		{
			if($cartDescriptionBeforePartNumber == 1)
			{
				$quoteListContent .= <<<HTML
<div class="eicpCartItemField eicpCartItemDescription">
<div class="eicpCartItemName">$cart_item_name</div>
$descriptionContent
$propertyListContent
$cart_item_comment
{$item['customContent']}
</div>
<div class="eicpCartItemField eicpCartItemPartNumber">$cart_item_part_number_display</div>
HTML;
			}
			else
			{
				$quoteListContent .= <<<HTML
<div class="eicpCartItemField eicpCartItemPartNumber">$cart_item_part_number_display</div>
<div class="eicpCartItemField eicpCartItemDescription">
<div class="eicpCartItemName">$cart_item_name</div>
$descriptionContent
$propertyListContent
$cart_item_comment
{$item['customContent']}
</div>
HTML;
			}
		}
		$quoteListContent .= <<<HTML
<div class="eicpCartItemField eicpCartItemQuantity">$quantityField</div>
<div class="eicpCartItemField eicpCartItemRemove"><button type="button" class="eicpButton eicpRemoveButton $removeButtonClass" onclick="eICP.cart.removeItem('$cart_item_id','$cart_item_name_safe')">$removeButtonText</button></div>
<div class="eicpClear"></div>
</div>
HTML;
	}
}

$cartTotal = money_format("%.2n",$cart['chargeList']['SUBTOTAL'][0]['cart_charge']);

#####################################################
## Totals & Coupons
#####################################################
if($useFormDictionary)
{
	$addCouponLabelText = $Content->dictionary['eICP_cart_add_coupon_label'];
	$addCouponButtonText = $Content->dictionary['eICP_cart_add_coupon_button'];
}
else
{
	$addCouponLabelText = trim($this->configure['cartAddCouponLabelText']) ?: 'Coupon Code';
	$addCouponButtonText = trim($this->configure['cartAddCouponButtonText']) ?: 'Add';
}
$cartAddCouponFormSeparate = $this->configure['cartAddCouponFormSeparate'];
$cartForceCouponForm = $this->configure['cartForceCouponForm'];
$cartHideCouponForm = $this->configure['cartHideCouponForm'];
$couponList = array();
if(!$cartHideCouponForm)
{
	$couponList = $this->admin->couponList(array('search_coupon_status'=>'ACTIVE'));
}
$showCouponForm = ((sizeof($couponList) || $cartForceCouponForm) && !$cartHideCouponForm);
$cartCouponList = $cart['couponList'];
$cartTotalsHeader = trim($useFormDictionary ? $Content->dictionary['eICP_cart_totals_header'] : $this->configure['cartTotalsHeaderText']);
if(strlen($cartTotalsHeader))
{
	$cartTotalsHeader = <<<HTML
<h3 class="eicpCartTotalsHeader">$cartTotalsHeader</h3>
HTML;
}
$cartTotalContent = '';
$inlineCouponForm = '';
if($showCouponForm && !$cartAddCouponFormSeparate)
{
	$inlineCouponForm = <<<HTML
<div class="eicpCartTotalRow eicpCartAddCoupon">
<div class="eicpPriceFieldTitle eicpCartAddCouponLabel">$addCouponLabelText</div>
<div class="eicpPriceField eicpCartAddCouponField">
<input type="text" size="30" id="coupon_code" name="coupon_code" class="eicpInput eicpCouponCodeInput" />
<button type="button" class="eicpButton eicpAddCouponButton" onclick="eICP.cart.addCoupon()">$addCouponButtonText</button>
</div>
<div class="eicpClear"></div>
</div>
<div class="eicpCouponMessage" id="eicpCouponMessage"></div>
HTML;
}

$updateButtonText = $useFormDictionary ? $Content->dictionary['eICP_cart_totals_update'] : 'Update';
$updateContent = <<<HTML
<div class="eicpUpdateMessage" style="display: none;"></div>
<button type="button" class="eicpButton eicpUpdateButton eicpCheckout" onclick="eICP.cart.updateItemList()" style="display: none;">$updateButtonText</button>
<button type="button" class="eicpButton eicpUpdateButton eicpCheckout" onclick="eICP.checkout.itemList()" style="display: none;">$cancelButtonText</button>
<div class="eicpClear"></div>
HTML;

if($quoteList || $showCartItemCommentField)
{
	$updatePromptText = $useFormDictionary ? $Content->dictionary['eICP_cart_update_prompt'] : 'Click "Update" to save changes';
}
else
{
	$updatePromptText = $useFormDictionary ? $Content->dictionary['eICP_cart_totals_update_prompt'] : 'Click "Update" for new totals';
}
$updatePromptText = addslashes($updatePromptText);

if($useFormDictionary)
{
	$cartTotalLabelText = $Content->dictionary['eICP_charge_item'];
	$totalLabelText = $Content->dictionary['eICP_charge_total'];
}
else
{
	$cartTotalLabelText = 'Cart Total';
	$totalLabelText = 'Total';
}
if(sizeof($cartCouponList))
{
	$itemTotal = money_format("%.2n",$cart['chargeList']['ITEM'][0]['cart_charge']);
	$rowClass = 'eicpGrey1';
	$cartTotalContent = <<<HTML
<div class="eicpCartTotals">
$cartTotalsHeader
<div class="eicpCartTotalRow eicpCartSubtotal $rowClass">
<div class="eicpPriceFieldTitle">$cartTotalLabelText</div>
<div class="eicpPriceField">$itemTotal</div>
<div class="eicpClear"></div>
</div>
HTML;
	foreach($cartCouponList as $coupon)
	{
		$rowClass = $rowClass == 'eicpGrey2' ? 'eicpGrey1' : 'eicpGrey2';
		$cart_coupon_value = money_format("%.2n",$coupon['cart_coupon_value']);
		$cartTotalContent .= <<<HTML
<div class="eicpCartTotalRow eicpCartCoupon $rowClass">
<div class="eicpPriceFieldTitle eicpCartCouponTitle">{$coupon['cart_coupon_title']}</div>
<div class="eicpPriceField eicpCartCouponValue">$cart_coupon_value</div>
<div class="eicpClear"></div>
</div>
HTML;
	}
	$cartTotalContent .= <<<HTML
$inlineCouponForm
<div class="eicpCartTotalRow eicpCartTotal $rowClass">
<div class="eicpPriceFieldTitle">$totalLabelText</div>
<div class="eicpPriceField">$cartTotal</div>
<div class="eicpClear"></div>
</div>
{$cart['chargeList']['customContent']}
$updateContent
</div>
HTML;
}
else
{
	$cartTotalContent = <<<HTML
<div class="eicpCartTotals">
$cartTotalsHeader
<div class="eicpCartTotalRow eicpCartSubtotal $rowClass">
<div class="eicpPriceFieldTitle">$cartTotalLabelText</div>
<div class="eicpPriceField">$cartTotal</div>
<div class="eicpClear"></div>
</div>
{$cart['chargeList']['customContent']}
$inlineCouponForm
$updateContent
</div>
HTML;
}

$couponFormContent = '';
if($showCouponForm && $cartAddCouponFormSeparate)
{
	$couponFormHeader = trim($useFormDictionary ? $Content->dictionary['eICP_cart_add_coupon_header'] : $this->configure['cartAddCouponFormHeaderText']);
	if(strlen($couponFormHeader))
	{
		$couponFormHeader = <<<HTML
<h3 class="eicpCartAddCouponHeader">$couponFormHeader</h3>
HTML;
	}
	$couponFormContent = <<<HTML
<div class="eicpCartAddCoupon">
$couponFormHeader
<label for="coupon_code" class="eicpFormLabel eicpCartAddCouponLabel">$addCouponLabelText</label>
<div class="eicpFormField eicpCartAddCouponField">
<input type="text" size="30" id="coupon_code" name="coupon_code" class="eicpInput eicpCouponCodeInput" />
<button type="button" class="eicpButton eicpAddCouponButton" onclick="eICP.cart.addCoupon()">$addCouponButtonText</button>
</div>
<div class="eicpClear"></div>
<div class="eicpCouponMessage" id="eicpCouponMessage"></div>
</div>
HTML;
}

#####################################################
## Page Layout
#####################################################
$cartContent = '';
if(sizeof($itemList) || sizeof($quoteList))
{
	$cartContent .= <<<HTML
<div class="eicpCartContent">
HTML;
	$cartButtonOrder = explode(',',$this->configure['cartButtonOrder']);
	$cartButtonOrderDefault = array('continueShopping','emptyCart','checkout');
	// If the array from the config does not contain the same values as the default, ignore it.
	if(sizeof(array_diff($cartButtonOrder,$cartButtonOrderDefault)) || sizeof(array_diff($cartButtonOrderDefault,$cartButtonOrder)))
	{
		$cartButtonOrder = $cartButtonOrderDefault;
	}
	$cartButtonContent = '';
	foreach($cartButtonOrder as $button)
	{
		if($button == 'continueShopping')
		{
			$cartButtonContent .= $continueShoppingButton;
		}
		if($button == 'emptyCart')
		{
			$cartButtonContent .= $emptyCartButton;
		}
		if($button == 'checkout')
		{
			$cartButtonContent .= $checkoutButton;
		}
	}

	if(sizeof($itemList))
	{
		$cartItemListHeader = trim($useFormDictionary ? $Content->dictionary['eICP_cart_items_header'] : $this->configure['cartItemListHeaderText']);
		if(strlen($cartItemListHeader))
		{
			$cartItemListHeader = <<<HTML
<h2 class="eicpCartItemHeader">$cartItemListHeader</h2>
HTML;
		}
		$cartContent .= <<<HTML
$cartItemListHeader
<div class="eicpCartItemList">
HTML;
		if($cartItemsNonTabular != 1)
		{
			$cartContent .= <<<HTML
<div class="eicpCartItemHeaders">
<div class="eicpCartItemHeader eicpCartItemImage">$cartItemImageHeader</div>
HTML;
			if($cartCombinePartNumberDescription)
			{
				$cartContent .= <<<HTML
<div class="eicpCartItemHeader eicpCartItemInfo">$cartItemNameLabelText</div>
HTML;
			}
			elseif($cartDescriptionBeforePartNumber)
			{
				$cartContent .= <<<HTML
<div class="eicpCartItemHeader eicpCartItemDescription">$cartItemNameLabelText</div>
<div class="eicpCartItemHeader eicpCartItemPartNumber">$cartItemPartNumberLabelText</div>
HTML;
			}
			else
			{
				$cartContent .= <<<HTML
<div class="eicpCartItemHeader eicpCartItemPartNumber">$cartItemPartNumberLabelText</div>
<div class="eicpCartItemHeader eicpCartItemDescription">$cartItemNameLabelText</div>
HTML;
			}
			$cartContent .= <<<HTML
<div class="eicpCartItemHeader eicpCartItemPrice eicpPriceField">$cartItemPriceLabelText</div>
<div class="eicpCartItemHeader eicpCartItemQuantity">$cartItemQuantityLabelText</div>
<div class="eicpCartItemHeader eicpCartItemTotal eicpPriceField">$cartItemTotalLabelText</div>
<div class="eicpCartItemHeader eicpCartItemRemove">$removeHeaderText</div>
<div class="eicpClear"></div>
</div>
HTML;
		}
		$cartContent .= <<<HTML
$itemListContent
<div class="eicpClear"></div>
</div>
<div class="eicpCartTotalsCoupons">
$cartTotalContent
$couponFormContent
<div class="eicpClear"></div>
HTML;
		if(!sizeof($quoteList))
		{
			$cartContent .= <<<HTML
<div class="eicpCartButtons">
$cartButtonContent
<div class="eicpClear"></div>
</div>
HTML;
		}
		$cartContent .= <<<HTML
</div>
<div class="eicpClear"></div>
HTML;
	}
	if(sizeof($quoteList))
	{
		$quoteItemListHeaderText = $useFormDictionary ? $Content->dictionary['eICP_quote_items_header'] : (trim($this->configure['quoteItemListHeaderText']) ?: 'Quote Items');
		$cartContent .= <<<HTML
<h2 class="eicpQuoteItemHeader">$quoteItemListHeaderText</h2>
<form id="checkoutQuoteListForm">
<div class="eicpCartQuoteItemList">
HTML;
		if($cartItemsNonTabular != 1)
		{
			$cartContent .= <<<HTML
<div class="eicpCartItemHeaders">
<div class="eicpCartItemHeader eicpCartItemImage">$cartItemImageHeader</div>
HTML;
			if($cartCombinePartNumberDescription)
			{
				$cartContent .= <<<HTML
<div class="eicpCartItemHeader eicpCartItemInfo">$cartItemNameLabelText</div>
HTML;
			}
			elseif($cartDescriptionBeforePartNumber)
			{
				$cartContent .= <<<HTML
<div class="eicpCartItemHeader eicpCartItemDescription">$cartItemNameLabelText</div>
<div class="eicpCartItemHeader eicpCartItemPartNumber">$cartItemPartNumberLabelText</div>
HTML;
			}
			else
			{
				$cartContent .= <<<HTML
<div class="eicpCartItemHeader eicpCartItemPartNumber">$cartItemPartNumberLabelText</div>
<div class="eicpCartItemHeader eicpCartItemDescription">$cartItemNameLabelText</div>
HTML;
			}
			$cartContent .= <<<HTML
<div class="eicpCartItemHeader eicpCartItemQuantity">$cartItemQuantityLabelText</div>
<div class="eicpCartItemHeader eicpCartItemRemove">$removeHeaderText</div>
<div class="eicpClear"></div>
</div>
HTML;
		}
		$cartContent .= <<<HTML
$quoteListContent
<div class="eicpClear"></div>
</div>
<div class="eicpCartButtons">
$cartButtonContent
<div class="eicpClear"></div>
</div>
<div class="eicpClear"></div>
</form>
HTML;
	}
	$cartContent .= <<<HTML
</div>
HTML;
}
else
{
	$cartEmptyText = $useFormDictionary ? $Content->dictionary['eICP_cart_empty'] : 'Your cart is currently empty.';
	$cartContent = <<<HTML
<div class="eicpEmpty">
<p>$cartEmptyText</p>
<div class="eicpCartButtons">$continueShoppingButton</div>
</div>
HTML;
}

#####################################################
## Lead Time
#####################################################
$cartLeadTimeContent = '';

if(in_array($leadTimeDisplay, array('max', 'min', 'range', 'maxitem')))
{
	$cartLeadTimeContent = $this->leadTimeDisplay(array('cart'=>$cart, 'display_type'=>$leadTimeDisplay));

	if(strlen($cartLeadTimeContent))
	{
		$cartLeadTimeContent = <<<HTML
<div class="eicpCartLeadTime">$cartLeadTimeContent</div>
HTML;
	}
}

#####################################################
## Breadcrumbs
#####################################################
if($useFormDictionary)
{
	$checkoutBreadcrumbCart = $Content->dictionary['eICP_cart_breadcrumb'];
	$checkoutBreadcrumbShipping = $Content->dictionary['eICP_address_breadcrumb'];
	$checkoutBreadcrumbPayment = $Content->dictionary['eICP_payment_breadcrumb'];
}
else
{
	$checkoutBreadcrumbCart = trim($this->configure['checkoutBreadcrumbCart']) ?: 'Shopping Cart';
	$checkoutBreadcrumbCart = html_entity_decode($checkoutBreadcrumbCart);
	$checkoutBreadcrumbShipping = trim($this->configure['checkoutBreadcrumbShipping']) ?: 'Billing and Shipping';
	$checkoutBreadcrumbShipping = html_entity_decode($checkoutBreadcrumbShipping);
	$checkoutBreadcrumbPayment = trim($this->configure['checkoutBreadcrumbPayment']) ?: 'Review and Payment';
	$checkoutBreadcrumbPayment = html_entity_decode($checkoutBreadcrumbPayment);
}

$checkoutBreadcrumbDivider = trim($this->configure['checkoutBreadcrumbDivider']);

if(strlen($checkoutBreadcrumbDivider))
{
	$checkoutBreadcrumbDivider = html_entity_decode($checkoutBreadcrumbDivider);
	$checkoutBreadcrumbDivider = <<<HTML
<span class="eicpBreadcrumbDivider">$checkoutBreadcrumbDivider</span>
HTML;
}

$checkoutBreadcrumbAfterHeading = $this->configure['checkoutBreadcrumbAfterHeading'];

$checkoutBreadcrumbs = <<<HTML
<div class="eicpCheckoutBreadcrumbs">
<span class="eicpBreadcrumb eicpBreadcrumbCurrent eicpBreadcrumbCart">$checkoutBreadcrumbCart</span>
$checkoutBreadcrumbDivider
<span class="eicpBreadcrumb eicpBreadcrumbShipping">$checkoutBreadcrumbShipping</span>
$checkoutBreadcrumbDivider
<span class="eicpBreadcrumb eicpBreadcrumbPayment">$checkoutBreadcrumbPayment</span>
<div class="eicpClear"></div>
</div>
HTML;

#####################################################
## Header
#####################################################
$cartHeaderText = $useFormDictionary ? $Content->dictionary['eICP_cart_header'] : 'Shopping Cart';
$formHeader = <<<HTML
<div class="eicpCheckoutHeader">
HTML;

if(!$checkoutBreadcrumbAfterHeading)
{
	$formHeader .= $checkoutBreadcrumbs;
}

if(!$expressCheckout)
{
	$formHeader .= <<<HTML
<h1>$cartHeaderText</h1>
HTML;
}

if($checkoutBreadcrumbAfterHeading)
{
	$formHeader .= $checkoutBreadcrumbs;
}

$formHeader .= <<<HTML
<div class="eicpClear"></div>
</div>
HTML;

#####################################################
## JSON Data
#####################################################
// This reformatting of the data into separate arrays
// is intended to serve 2 purposes:
// 1) It makes the data easier to work with in
//    JavaScript by shortening the array keys.
// 2) It makes it less easy for someone to figure
//    out our table structures by looking at the
//    JSON output.
// -- Aaron Sherbeck, 2/21/18

$cart_currency = $cart['cart_currency'] ?: $this->configure['defaultCurrency'] ?: 'USD';

#####################################################
## JSON Item Data
#####################################################
$mergedItemList = array_merge($itemList, $quoteList);
$jsonItemList = [];

foreach($mergedItemList as $item)
{
	$jsonItem = array();
	$jsonItem['id'] = (int) $item['cart_item_id'];
	$jsonItem['name'] = $item['cart_item_name'];
	$jsonItem['partNumber'] = $item['cart_item_part_number'];
	$jsonItem['price'] = round($item['cart_item_price'], 2);
	$jsonItem['currency'] = $cart_currency;
	$jsonItem['priceBypass'] = (bool) (int) $item['cart_item_price_bypass'];
	$jsonItem['quantity'] = (int) $item['cart_item_quantity'];
	$jsonItem['quoteItem'] = (bool) (int) $item['cart_item_quote'];
	$jsonItemList[] = $jsonItem;
}

#####################################################
## JSON Coupon Data
#####################################################
$jsonCouponList = [];

foreach($cartCouponList as $coupon)
{
	$jsonCoupon = array();
	$jsonCoupon['id'] = (int) $coupon['coupon_id'];
	$jsonCoupon['title'] = $coupon['cart_coupon_title'];
	$jsonCoupon['value'] = round($coupon['cart_coupon_value'], 2);
	$jsonCoupon['currency'] = $cart_currency;
	$jsonCouponList[] = $jsonCoupon;
}

#####################################################
## JSON Charges
#####################################################
$jsonCharges = [];
$jsonCharges['item'] = round($cart['chargeList']['ITEM'][0]['cart_charge'], 2);
$jsonCharges['coupon'] = round($cart['chargeList']['COUPON'][0]['cart_charge'], 2);
$jsonCharges['subtotal'] = round($cart['chargeList']['SUBTOTAL'][0]['cart_charge'], 2);
$jsonCharges['currency'] = $cart_currency;

#####################################################
## Bolt JS
#####################################################
$boltJS = '';

if($this->bolt_publish_key)
{
	$cart_id = json_encode((int) $cart['cart_id']);
	$showMemberLogin = json_encode($showMemberLogin);
	$successRedirect = 'eICP.checkout.success();';

	if($this->configure['checkoutSuccessStopRedirect'] && (isTEST() || isDEVEL()))
	{
		$successRedirect = '';
	}

	// --------------------------------------------------
	// https://docs.bolt.com/checkout_button
	// --------------------------------------------------
	$boltJS = <<<JAVASCRIPT
(function(eICP) {
	try {
		eICP.checkout.showMemberLogin = {$showMemberLogin};
		var boltOrderToken, resolveCart;
		var boltOrderHints, resolveHints;

		eICP.bolt.configure(
			// cart
			new Promise(function(resolve, reject) {
				resolveCart = resolve;
			}),

			// hints
			new Promise(function(resolve, reject) {
				resolveHints = resolve;
			}),

			// callbacks
			{
				check: function() {
					if (boltOrderToken) {
						return true;
					}

					if (eICP.checkout.showMemberLogin) {
						eICP.checkout.memberLogin();
						return false;
					}

					eICP.getPage('command=eicpStartBoltCheckout', '', function(response) {
						var errorMessage = "Oops, something went wrong! Please try again. If the problem persists, contact customer support.";

						try {
							response = JSON.parse(response);

							if (response.status === 'success' && response.token) {
								boltOrderToken = response.token;
								boltOrderHints = $.isPlainObject(response.hints) ? response.hints : {};
								resolveHints(boltOrderHints);
								resolveCart({ orderToken: boltOrderToken });
								return;
							}

							if (response.message) {
								eICP.popup.error(response.message);
								return;
							}

							eICP.popup.error(errorMessage);
						}
						catch (err) {
							eICP.popup.error(errorMessage, err);
						}
					});

					return true;
				},

				success: function(transaction, callback) {
					var params = {
						command: 'eicpPlaceOrder',
						payment_type: 'bolt',
						cart_id: {$cart_id},
						bolt_transaction_reference: transaction.reference
					};

					eICP.getPage($.param(params), '', function(response) {
						var errorMessage = "Oops, something went wrong! Please contact customer support.";

						try {
							response = response.split('|', 2);

							if (response[0] == '1') {
								callback();
								{$successRedirect}
								return;
							}

							eICP.popup.error(response[1] || errorMessage);
						}
						catch (err) {
							eICP.popup.error(errorMessage, err);
						}

						callback();
					});
				}
			}
		);
	}
	catch (err) {
		eICP.popup.error("Oops, something went wrong! Make sure you're using an up-to-date, modern web browser.", err);
	}
})(eICP);
JAVASCRIPT;
}

?>
<?
#####################################################
## HTML
#####################################################
?>
<form id="checkoutItemListForm">
<?=$formHeader;?>
<?=$cartContent;?>
<div class="eicpClear"></div>
</form>
<?=$cartLeadTimeContent;?>
<?=$viewCartCustomMessage;?>
<?
#####################################################
## JAVASCRIPT
#####################################################
?>
<script type="text/javascript" src="/site/ecmp/<?=$_SESSION['codeBase'];?>/addons/inputmask/js/jquery.inputmask.js"></script>
<script type="text/javascript">
(function(eICP, $) {

	eICP.checkout.cartItems = <?=ltrim(u::indentCode(json_encode($jsonItemList, JSON_PRETTY_PRINT), 1));?>;
	eICP.checkout.coupons = <?=ltrim(u::indentCode(json_encode($jsonCouponList, JSON_PRETTY_PRINT), 1));?>;
	eICP.checkout.charges = <?=ltrim(u::indentCode(json_encode($jsonCharges, JSON_PRETTY_PRINT), 1));?>;

	eICP.cart = {};

	eICP.cart.cacheOldCartData = function() {
		eICP.checkout.oldCartDataCached = true;
		eICP.checkout.cartItemsOld = eICP.checkout.cartItems;
		eICP.checkout.couponsOld = eICP.checkout.coupons;
	};

	eICP.cart.compareCartData = function() {
		if (!eICP.checkout.oldCartDataCached) {
			return;
		}

		// Comparing Cart Items
		if (eICP.checkout.cartItems.length || eICP.checkout.cartItemsOld.length) {
			var cartItemsAdded = [];
			var cartItemsRemoved = [];

			var cartItemOldIndex = {};
			if (eICP.checkout.cartItemsOld.length) {
				$.each(eICP.checkout.cartItemsOld, function(i, item) {
					cartItemOldIndex[item.id] = item;
				});
			}

			var cartItemIndex = {};

			if (eICP.checkout.cartItems.length) {
				$.each(eICP.checkout.cartItems, function(i, item) {
					cartItemIndex[item.id] = item;

					if (cartItemOldIndex[item.id]) {
						var oldItem = cartItemOldIndex[item.id];

						if (item.quantity > oldItem.quantity) {
							var itemAdded = $.extend({}, item);
							itemAdded.quantityAdded = item.quantity - oldItem.quantity;
							cartItemsAdded.push(itemAdded);
						}
						else if (item.quantity < oldItem.quantity) {
							var itemRemoved = $.extend({}, item);
							itemRemoved.quantityRemoved = oldItem.quantity - item.quantity;
							cartItemsRemoved.push(itemRemoved);
						}
					}
					else {
						var itemAdded = $.extend({}, item);
						itemAdded.quantityAdded = item.quantity;
						cartItemsAdded.push(itemAdded);
					}
				});
			}

			if (eICP.checkout.cartItemsOld.length) {
				$.each(eICP.checkout.cartItemsOld, function(i, item) {
					if (!cartItemIndex[item.id]) {
						var itemRemoved = $.extend({}, item);
						itemRemoved.quantityRemoved = item.quantity;
						cartItemsRemoved.push(itemRemoved);
					}
				});
			}

			if (cartItemsAdded.length) {
				eICP.checkout.pageEvent('addItem.eICP.checkout.{{page}}', null, { cartItemsAdded: cartItemsAdded });
			}

			if (cartItemsRemoved.length) {
				eICP.checkout.pageEvent('removeItem.eICP.checkout.{{page}}', null, { cartItemsRemoved: cartItemsRemoved });
			}
		}

		// Comparing Coupons
		if (eICP.checkout.coupons.length || eICP.checkout.couponsOld.length) {
			var couponsAdded = [];
			var couponsRemoved = [];
			var couponsChanged = [];

			var couponOldIndex = {};

			if (eICP.checkout.couponsOld.length) {
				$.each(eICP.checkout.couponsOld, function(i, coupon) {
					couponOldIndex[coupon.id] = coupon;
				});
			}

			var couponIndex = {};

			if (eICP.checkout.coupons.length) {
				$.each(eICP.checkout.coupons, function(i, coupon){
					couponIndex[coupon.id] = coupon;

					if (couponOldIndex[coupon.id]) {
						var oldCoupon = couponOldIndex[coupon.id];

						if (coupon.value != oldCoupon.value) {
							var couponChanged = $.extend({}, coupon);
							couponChanged.valueChanged = coupon.value - oldCoupon.value;
							couponChanged.valueChanged = parseFloat(couponChanged.valueChanged.toFixed(2));
							couponsChanged.push(couponChanged);
						}
					}
					else {
						var couponAdded = $.extend({}, coupon);
						couponsAdded.push(couponAdded);
					}
				});
			}

			if (eICP.checkout.couponsOld.length) {
				$.each(eICP.checkout.couponsOld, function(i, coupon) {
					if (!couponIndex[coupon.id]) {
						var couponRemoved = $.extend({}, coupon);
						couponsRemoved.push(couponRemoved);
					}
				});
			}

			if (couponsAdded.length) {
				eICP.checkout.pageEvent('addCoupon.eICP.checkout.{{page}}', null, { couponsAdded: couponsAdded });
			}

			if (couponsRemoved.length) {
				eICP.checkout.pageEvent('removeCoupon.eICP.checkout.{{page}}', null, { couponsRemoved: couponsRemoved });
			}

			if (couponsChanged.length) {
				eICP.checkout.pageEvent('changeCoupon.eICP.checkout.{{page}}', null, { couponsChanged: couponsChanged });
			}
		}

		eICP.checkout.oldCartDataCached = false;
	};

	eICP.cart.removeItem = function(cart_item_id,cart_item_name) {
		<?=ltrim(u::indentCode($removeItemFunction, 2));?>
	};

	eICP.cart.emptyCart = function() {
		var alertParams = {};
		alertParams.message = "<?=$emptyCartConfirmationText;?>";
		alertParams.messageClass = 'eicpCartEmptyConfirmationMessage';
		alertParams.buttons = [];
		alertParams.buttons.push({
			label: '<?=$emptyCartButtonTextEscaped;?>',
			className: 'eicpCartConfirmEmptyButton',
			click: function() {
				eICP.popup.close();
				eICP.loader.set('eICPCheckout');
				eICP.cart.cacheOldCartData();
				eICP.getPage('&command=eicpResetCart','',eICP.checkout.itemList);
			}
		});
		alertParams.buttons.push({
			label: <?=$cancelButtonTextJSSafe;?>,
			className: 'eicpCartCancelEmptyButton',
			click: eICP.popup.close
		});
		alertParams.buttonsClass = 'eicpCartEmptyConfirmationButtons';
		eICP.popup.alert(alertParams);
	};

	eICP.cart.hideTotals = function() {
		$('<?=$itemTotalDisplaySelector;?>, .eicpCartSubtotal > .eicpPriceField, .eicpCartTotal > .eicpPriceField').html('---');
		eICP.cart.showUpdateButtons();
	};

	eICP.cart.showUpdateButtons = function() {
		$('.eicpUpdateMessage, .eicpUpdateButton').css('display','');
		$('#eicpCheckoutButton').hide();
		$('.eicpUpdateMessage').html("<?=$updatePromptText;?>");
	};

	eICP.cart.updateItemList = function() {
		var params = $('#checkoutItemListForm').serialize();
		eICP.loader.set('eICPCheckout');
		eICP.cart.cacheOldCartData();
		eICP.getPage('&command=eicpUpdateItemList&'+params,'',eICP.checkout.itemList);
	};

	eICP.cart.addCoupon = function() {
		var params = $('#checkoutItemListForm').serialize();
		eICP.loader.set('eICPCheckout');
		eICP.cart.cacheOldCartData();
		eICP.getPage('&command=eicpAddCoupon&'+params,'POPUP',eICP.checkout.itemList);
	};

	eICP.cart.breadPreQualify = function() {
		var breadOptions = <?=json_encode($breadOptions);?>;

		if (typeof bread == 'undefined' || !$.isPlainObject(breadOptions)) {
			return;
		}

		breadOptions.buttonLocation = 'cart_summary';
		breadOptions.allowCheckout = false;
		breadOptions.done = function(err, tx_token) {};

		bread.showCheckout(breadOptions);
	};

	<?=ltrim(u::indentCode($boltJS, 1));?>

	$(document).ready(function() {
		eICP.cart.compareCartData();

		var $quantityInputs = $('.eicpQuantityInput');

		$quantityInputs.inputmask('9{*}');
		$quantityInputs.on('focus', function() {
			var $input = $(this);
			$input.data('value', $input.val());
		});
		$quantityInputs.on('input change', function() {
			var $input = $(this);

			if ($input.val() != $input.data('value')) {
				if ($input.closest('.eicpCartQuoteItemList').length) {
					eICP.cart.showUpdateButtons();
				}
				else {
					eICP.cart.hideTotals();
				}
			}
		});

		$('.eicpCommentInput').on('input change', eICP.cart.showUpdateButtons);
	});

})(eICP, jQuery);
</script>
