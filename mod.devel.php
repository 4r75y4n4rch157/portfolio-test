<?php
/*
####################################################
Any AJAX call with command=mod_* in the query
string will be routed to this function. Call the
appropriate mod function(s) based on the command.
The $mast, $site, and $debug paramters can be
safely ignored.
####################################################
 */
function mod_dispatcher($command, $mast, $site, $debug)
{
	switch($command)
	{
	case 'mod_buildProductLineGallery':
		$params = array();
		$params['xid'] = getSafeFormValue('xid');
		$params['folder'] = getSafeFormValue('folder');
		echo json_encode(mod_buildProductLineGallery($params));
		break;
	case 'mod_setCategoryTileSort':
		$params = array();
		$params['sort'] = getSafeFormValue('sort');
		mod_setCategoryTileSort($params);
		break;
	case 'mod_getResponseValues' :
		$params = array();
		$params['response_id'] = getSafeFormValue('response_id');
		mod_getResponseValues($params);
		break;
	case 'mod_promotionBar' :

		$getPage = getSafeFormValue('getPage');

		if ( ! $getPage )
		{
			return;
		}

		$promotion_value_get = getSafeFormValue('promotionValueGet');

		if ( $promotion_value_get )
		{
			echo json_encode([
				'value' => $_SESSION['promotion_bar_session_close']
			]);
			return;
		}

		$_SESSION['promotion_bar_session_close'] = getSafeFormValue('promotionValue');

		break;
	case 'mod_getPartIDFromFitment':
		$params = [
			'MakeID' => getSafeFormValue('MakeID'),
			'ModelID' => getSafeFormValue('ModelID'),
			'YearID' => getSafeFormValue('YearID'),
			'CategoryID' => getSafeFormValue('CategoryID'),
			'BedLengthID' => getSafeFormValue('BedLengthID'),
			'BodyTypeID' => getSafeFormValue('BodyTypeID'),
			'BedTypeID' => getSafeFormValue('BedTypeID'),
			'SubmodelID' => getSafeFormValue('SubModelID')
		];
		mod_getPartIDFromFitment($params);
		break;
	case 'mod_PS_getYears':
		$categoryID = getSafeFormValue('categoryID');
		mod_PS_getYears($categoryID);
		break;

	case 'mod_PS_getMakes':
		$categoryID = getSafeFormValue('categoryID');
		$yearID = getSafeFormValue('yearID');
		mod_PS_getMakes($categoryID, $yearID);
		break;
	case 'mod_PS_getModels':
		$categoryID = getSafeFormValue('categoryID');
		$yearID = getSafeFormValue('yearID');
		$makeID = getSafeFormValue('makeID');
		mod_PS_getModels($categoryID, $yearID, $makeID);
		break;

	case 'mod_PS_getFAQ':
		$categoryID = getSafeFormValue('categoryID');
		$yearID = getSafeFormValue('yearID');
		$makeID = getSafeFormValue('makeID');
		$modelID = getSafeFormValue('modelID');
		$setFAQs = getSafeFormValue('setFAQs');

		mod_PS_getFAQ($categoryID, $yearID, $makeID, $modelID, $setFAQs);
		break;



	}
}

####################################################
## Runs before any template activity happens,
## but after page cache
####################################################
function mod_preTemplate()
{
	mod_urlVehicleSession();
}




#########################################################
## Product Selector Mod Functions
##
#########################################################


function mod_PS_getYears($categoryID)
{
   	global $d;
	global $dt;
	global $core;
	global $myModules;
	global $siteInformation;
	$ePIM = $myModules->modules['ePIM'];

	$params = [];
	$params['CategoryID'] = $categoryID;
	$params['column'] = "YearID";
	$years= $ePIM->p->fitmentList($params);
	return $years;
}


function mod_PS_getMakes($categoryID, $yearID)
{
	global $d;
	global $dt;
	global $core;
	global $myModules;
	global $siteInformation;
	$ePIM = $myModules->modules['ePIM'];

	$params = [];
	$params['CategoryID'] = $categoryID;
	$params['YearID'] = $yearID;
	$params['column'] = "MakeID";
	$makes = $ePIM->p->fitmentList($params);
	header('Content-Type: application/json');
	echo json_encode($makes);
}




function mod_PS_getModels($categoryID, $yearID, $makeID)
{
	global $d;
	global $dt;
	global $core;
	global $myModules;
	global $siteInformation;
	$ePIM = $myModules->modules['ePIM'];

	$params = [];
	$params['CategoryID'] = $categoryID;
	$params['YearID'] = $yearID;
	$params['MakeID'] = $makeID;
	$params['column'] = "ModelID";
	$models = $ePIM->p->fitmentList($params);
	header('Content-Type: application/json');
	echo json_encode($models);
	return;
}


//Product Selector - Fitment Attribute Qualifiers
function mod_PS_getFAQ($categoryID, $yearID, $makeID, $modelID, $setFAQs)
{
	global $d;
	global $dt;
	global $core;
	global $myModules;
	global $siteInformation;
	$ePIM = $myModules->modules['ePIM'];

	$params = [];
	$params['CategoryID'] = $categoryID;
	$params['YearID'] = $yearID;
	$params['MakeID'] = $makeID;
	$params['ModelID'] = $modelID;
    $fitment_attribute_qualifier_list = $ePIM->p->fitmentAttributeQualifierList($params);
	$faqSettings = [];
	/*Example"
	 *"SubmodelID:Submodel,BedLengthID:Bed Length,BodyNumDoorsID:No. of Doors"
	 * */
	$faqSettings = explode(",", $settings['mod_PS_Fitment_Qualifiers']);
	$faqs = [];

	foreach($faqSettings as $index => $setting)
	{
		$faqs[$index]['value'] = 'FitmentAttributeCodeID';
		$faqs[$index]['name'] = $fitment_attribute_qualifier_list[0]['FitmentAttributeCodeID'];
	}

    //prepare previously selected in params
	foreach($setFaqs as $setFaq)
	{
		$params[$setFaq['id']] = $setFaq['value'];
	}

	$results = [];
	$nextFaqIndex = 0;
	while(count($results) <= 1 && $nextFaqIndex < count($faqs))
	{
		$params['column'] = $faqs[$nextFaqIndex]['id'];
		$results= $ePIM->p->fitmentList($params);
        $nextFaqIndex++;
	}





    /*So basically what needs to happen is:
        we determine if there are FAQs
        we take the first one and see if there are more than one option
        if so, send it back to be selectBoxed
        if not, set it as a param and look at the next one , repeat
        once all have been set, hit up the fitmentList to get product.
        */

	header('Content-Type: application/json');
	//echo json_encode($results);
	//echo json_encode($params);
	//echo json_encode($faqSettings);
    echo json_encode($fitment_attribute_qualifier_list);
    return;
}



####################################################
## Get meta description tag
####################################################
function mod_FormatMetaDescription($description)
{
	global $dt;


	if($dt == 80) // ePIM_Category
	{
		$description = mod_vehicleShortCodes($description);
	}
	elseif($dt == 126) // ePIM_PartSearch
	{
		$description = mod_productSearchShortCodes($description);
	}
	elseif($dt == 105) // pageSecure
	{
		$description = mod_memberShortCodes($description);
	}
	$description = htmlentities(trim($description), ENT_COMPAT | ENT_HTML401, 'UTF-8', false);



	return $description;
}


####################################################
## Get meta keywords tag
####################################################
function mod_FormatMetaKeywords($keywords)
{
	global $dt;

	if($dt == 80) // ePIM_Category
	{
		$keywords = mod_vehicleShortCodes($keywords);
	}
	elseif($dt == 126) // ePIM_PartSearch
	{
		$keywords = mod_productSearchShortCodes($keywords);
	}
	elseif($dt == 105) // pageSecure
	{
		$keywords = mod_memberShortCodes($keywords);
	}
	if(!is_array($keywords))
	{
		$keywords = explode(',',$keywords);
	}
	foreach($keywords as $i => $word)
	{
		$keywords[$i] = strtolower(trim($word));
	}
	$keywords = array_unique($keywords);
	$keywords = implode(', ',$keywords);
	$keywords = htmlentities(trim($keywords), ENT_COMPAT | ENT_HTML401, 'UTF-8', false);

	return $keywords;
}


####################################################
## Get Title tag text
####################################################
function mod_FormatTitleTagText($title)
{
	global $dt;
	global $myModules;

	if($dt == 105) // pageSecure
	{
		$Member = $myModules->modules['Member'];
		if(!$Member->memberCheckLogin($_SESSION['member_sid']))
		{
			$title = 'Please Log In';
		}
	}

	if(strpos($title, "|") !== false)
	{
		$titleArray = explode('|',$title);
		$title = array();
		foreach($titleArray as $i => $chunk)
		{
			if(trim($chunk) != $defaultPageTitle)
			{
				$title[] = trim($chunk);
			}
		}
		$title[] = $defaultPageTitle;
		$title = implode (' | ',$title);
	}


	if($dt == 80) // ePIM_Category
	{
		$title = mod_vehicleShortCodes($title);
	}
	elseif($dt == 126) // ePIM_PartSearch
	{
		$title = mod_productSearchShortCodes($title);
	}
	elseif($dt == 105) // pageSecure
	{
		$title = mod_memberShortCodes($title);
	}


	$title = htmlentities(trim($title), ENT_COMPAT | ENT_HTML401, 'UTF-8', false);


	return $title;
}


####################################################
## Formatting additional meta tags
####################################################
function mod_metaTags($tagList='')
{
	$metatags = '';
	if(is_array($tagList))
	{
		if(sizeof($tagList))
		{
			foreach($tagList as $tag)
			{
				if(is_array($tag))
				{
					if(((array_key_exists('name',$tag) && strlen($tag['name'])) || (array_key_exists('property',$tag) && strlen($tag['property']))) && (array_key_exists('content',$tag) && strlen($tag['content'])))
					{
						$tagAttributes = array();
						foreach($tag as $key => $value)
						{
							$tagAttributes[] = "{$key}=\"{$value}\"";
						}
						$tagAttributes = implode(' ',$tagAttributes);
						$metatags .= <<<HTML
<meta $tagAttributes />

HTML;
					}
				}
				else
				{
					$metatags .= trim($tag)."\n";
				}
			}
		}
	}
	else
	{
		$metatags .= trim($tagList)."\n";
	}
	return $metatags;
}

####################################################
## Retrieving UI text from the UI Dictionary
####################################################
function mod_uiText($key='',$default='')
{
	global $uiText;
	if(!is_array($uiText))
	{
		$uiText = array();
		$uiTextLookup = pageContentList('','','',array('d'=>1,'dt'=>86,'OULimit'=>1));
		if(sizeof($uiTextLookup['subContent']))
		{
			foreach($uiTextLookup['subContent'] as $content)
			{
				if($content['tableName'] == 'uiDictionaryItem' && strlen($content['content_key']))
				{
					$uiText[$content['content_key']] = trim($content['text']);
				}
			}
		}
	}
	if($key)
	{
		$value = getParams($key,$uiText);
		if(!strlen($value))
		{
			$value = $default;
		}
		return $value;
	}
	else
	{
		return $uiText;
	}
}


####################################################
## Retrieving a dictionary term with shortcode
## value substitutions
####################################################
function mod_getDictionaryTerm($params = array())
{
	return mod_applyShortcodes($params);
}

function mod_applyShortcodes($params = array())
{
	global $myModules;
	$Content = $myModules->modules['Content'];

	$text = getParams('text', $params);
	$dictionary_key = getParams('dictionary_key', $params);
	if(!strlen($text) && $dictionary_key && array_key_exists($dictionary_key, $Content->dictionary))
	{
		$text = $Content->dictionary[$dictionary_key];
	}
	$shortcode_value_array = getParams('shortcode_value_array', $params) ?: getParams('shortcode_value', $params) ?: getParams('shortcode', $params);
	if(strlen($text))
	{
		if(preg_match('/({(lc|uc)?{.+}}|{if .+}.*{endif})/i', $text))
		{
			$shortcodes = array();

			$replace_matches = false;
			$replace_or_matches = false;
			$if_matches = false;
			$compound_if_matches = false;

			$matches = array();
			preg_match_all('/{(lc|uc)?{([^}\|]+)(\|[^}]+)?}}/i', $text, $matches);
			if(sizeof(array_filter($matches[2])))
			{
				$replace_matches = true;
				$shortcodes = array_merge($shortcodes, array_filter($matches[2]));
				if(sizeof($matches[3]))
				{
					$replace_or_matches = true;
				}
			}

			$matches = array();
			preg_match_all('/{if ([^}\s]+)( (and|or|not) ([^}\s]+))?}.*?{endif}/i', $text, $matches);
			if(sizeof(array_filter($matches[1])))
			{
				$if_matches = true;
				$shortcodes = array_merge($shortcodes, array_filter($matches[1]));
				if(sizeof(array_filter($matches[4])))
				{
					$compound_if_matches = true;
					$shortcodes = array_merge($shortcodes, array_filter($matches[4]));
				}
			}

			if(sizeof($shortcodes))
			{
				$shortcodes = array_unique($shortcodes);
				$shortcode_values = array();
				foreach($shortcodes as $code)
				{
					$code = strtolower($code);
					$shortcode_values[$code] = '';
				}
				if(is_array($shortcode_value_array))
				{
					foreach($shortcode_value_array as $code => $value)
					{
						$code = strtolower($code);
						if(array_key_exists($code, $shortcode_values))
						{
							$shortcode_values[$code] = $value;
						}
					}
				}
				if($replace_matches)
				{
					$shortcode_find_array = array();
					$shortcode_replace_array = array();
					foreach($shortcode_values as $code => $value)
					{
						$shortcode_find_array[] = '';
						$shortcode_replace_array[] = $value;

						$shortcode_find_array[] = '{lc{'.$code.'}}';
						$shortcode_replace_array[] = strtolower($value);

						$shortcode_find_array[] = '{uc{'.$code.'}}';
						$shortcode_replace_array[] = strtoupper($value);
					}
					$text = str_ireplace($shortcode_find_array, $shortcode_replace_array, $text);
				}
				if($replace_or_matches)
				{
					foreach($shortcode_values as $code => $value)
					{
						$replace_regex = '//i';
						$replace_lower_regex = '/{lc{'.$code.'\|([^}]+)}}/i';
						$replace_upper_regex = '/{uc{'.$code.'\|([^}]+)}}/i';
						if($value)
						{
							$text = preg_replace($replace_regex, $value, $text);
							$text = preg_replace($replace_lower_regex, strtolower($value), $text);
							$text = preg_replace($replace_upper_regex, strtolower($value), $text);
						}
						else
						{
							$text = preg_replace($replace_regex, '$1', $text);
							$text = preg_replace($replace_lower_regex, '$1', $text);
							$text = preg_replace($replace_upper_regex, '$1', $text);
						}
						if(!$text)
						{
							break;
						}
					}
				}
				if($if_matches)
				{
					foreach($shortcode_values as $code => $value)
					{
						$if_else_regex = '/{if '.$code.'}(.*?){else}(.*?){endif}/i';
						$if_regex = '/{if '.$code.'}(.*?){endif}/i';
						if($value)
						{
							$text = preg_replace($if_else_regex, '$1', $text);
							$text = preg_replace($if_regex, '$1', $text);
						}
						else
						{
							$text = preg_replace($if_else_regex, '$2', $text);
							$text = preg_replace($if_regex, '', $text);
						}
						if($compound_if_matches)
						{
							foreach($shortcode_values as $code2 => $value2)
							{
								$if_and_else_regex = '/{if '.$code.' and '.$code2.'}(.*?){else}(.*?){endif}/i';
								$if_and_regex = '/{if '.$code.' and '.$code2.'}(.*?){endif}/i';
								if($value && $value2)
								{
									$text = preg_replace($if_and_else_regex, '$1', $text);
									$text = preg_replace($if_and_regex, '$1', $text);
								}
								else
								{
									$text = preg_replace($if_and_else_regex, '$2', $text);
									$text = preg_replace($if_and_regex, '', $text);
								}
								$if_or_else_regex = '/{if '.$code.' or '.$code2.'}(.*?){else}(.*?){endif}/i';
								$if_or_regex = '/{if '.$code.' or '.$code2.'}(.*?){endif}/i';
								if($value || $value2)
								{
									$text = preg_replace($if_or_else_regex, '$1', $text);
									$text = preg_replace($if_or_regex, '$1', $text);
								}
								else
								{
									$text = preg_replace($if_or_else_regex, '$2', $text);
									$text = preg_replace($if_or_regex, '', $text);
								}
								$if_not_else_regex = '/{if '.$code.' not '.$code2.'}(.*?){else}(.*?){endif}/i';
								$if_not_regex = '/{if '.$code.' not '.$code2.'}(.*?){endif}/i';
								if($value && !$value2)
								{
									$text = preg_replace($if_not_else_regex, '$1', $text);
									$text = preg_replace($if_not_regex, '$1', $text);
								}
								else
								{
									$text = preg_replace($if_not_else_regex, '$2', $text);
									$text = preg_replace($if_not_regex, '', $text);
								}
								if(!$text)
								{
									break;
								}
							}
						}
						if(!$text)
						{
							break;
						}
					}
				}

				if($text)
				{
					$html_tags = array('span', 'em', 'strong', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div');
					foreach($html_tags as $tag)
					{
						$regex = '/<'.$tag.'(\s[^>]*)?>[\s\t\n\r]*<\/'.$tag.'>/i';
						$text = preg_replace($regex, '', $text);
						if(!$text)
						{
							break;
						}
					}
				}
			}
		}
	}
	return $text;
}



####################################################
## Parsing the page URL
####################################################
function mod_parseURL()
{
	global $parsedURL;
	if(is_array($parsedURL))
	{
		return $parsedURL;
	}
	else
	{
		global $siteInformation;
		global $myModules;
		$Content = $myModules->modules['Content'];
		$parsedURL = array(
			'request_uri'=>array(),
			'language'=>'',
			'page_url'=>'',
			'url_params'=>array()
		);
		$requestURI = $_SERVER['REQUEST_URI'];
		if(strlen($_SERVER['QUERY_STRING']))
		{
			list($requestURI) = explode('?',$requestURI);
		}
		if(strlen($siteInformation['menubase']) && substr($requestURI,0,strlen($siteInformation['menubase'])) == $siteInformation['menubase'])
		{
			$requestURI = substr($requestURI,strlen($siteInformation['menubase']));
		}
		$requestURI = trim($requestURI,'/');
		$requestURI = strlen($requestURI) ? explode('/',$requestURI) : array();
		$parsedURL['request_uri'] = $requestURI;
		if(sizeof($requestURI))
		{
			if(preg_match('/^[a-zA-Z]{2}(-[a-zA-Z]{2})?$/',$requestURI[0]))
			{
				$languageURLCheck = $Content->languageList(array('language_url'=>$requestURI[0]));
				if($languageURLCheck)
				{
					$parsedURL['language'] = array_shift($requestURI);
				}
			}
			if(sizeof($requestURI))
			{
				$parsedURL['page_url'] = array_shift($requestURI);
			}
			$parsedURL['url_params'] = $requestURI;
		}
		return $parsedURL;
	}
}

####################################################
## Building a page URL
####################################################
function mod_buildURL($params=array())
{
	global $buildURLCache;
	if(!is_array($buildURLCache))
	{
		$buildURLCache = array();
	}
	$allowed_params = array('d','id','data_id','dt','data_table','page_url','url_name','query_string','search_params','search_values','no_vehicle_url','site_default','ou_site_default');
	if(is_array($params))
	{
		$params = array_intersect_key($params,array_fill_keys($allowed_params,''));
		ksort($params);
	}
	$serialized_params = serialize($params);
	if(array_key_exists($serialized_params,$buildURLCache))
	{
		return $buildURLCache[$serialized_params];
	}
	else
	{
		global $siteInformation;
		$d = getParams('d',$params) ?: getParams('id',$params) ?: getParams('data_id',$params);
		$dt = getParams('dt',$params) ?: getParams('data_table',$params);
		$page_url = getParams('page_url',$params) ?: getParams('url_name',$params);
		$query_string = getParams('query_string',$params);
		// If there is a page URL, search params will be appended in /key/value form.
		// Otherwise, they will be added to the query string in &key=value form.
		$search_params = getParams('search_params',$params);
		// If there is a page URL, search values will be appended in /value form (ignoring the keys).
		// Otherwise, they will be added to the query string in &key=value form.
		$search_values = getParams('search_values',$params);
		$no_vehicle_url = getParams('no_vehicle_url',$params);
		$site_default = getParams('site_default',$params) ?: getParams('ou_site_default',$params);
		if(!is_array($page_url))
		{
			$page_url = trim($page_url);
			$page_url = trim($page_url,'/');
			$page_url = strlen($page_url) ? explode('/',$page_url) : array();
		}
		$language_url = $_SESSION['language_url'];
		if($language_url && $page_url[0] == $language_url)
		{
			array_shift($page_url);
		}
		$query_params = array();
		if(is_array($query_string))
		{
			$query_params = $query_string;
		}
		else
		{
			if(substr($query_string,0,1) == '?')
			{
				$query_string = substr($query_string,1);
			}
			parse_str($query_string,$query_params);
		}
		if(sizeof($page_url))
		{
			if($dt == 80 && $_SESSION['fitment']['YearID'] && $_SESSION['fitment']['MakeName'])
			{
				$url_year = $_SESSION['fitment']['YearID'];
				$url_make = strtolower(str_replace(' ','-',trim($_SESSION['fitment']['MakeName'])));
				if(in_array($url_year,$page_url))
				{
					array_splice($page_url,array_search($url_year,$page_url),1);
				}
				if(in_array($url_make,$page_url))
				{
					array_splice($page_url,array_search($url_make,$page_url),1);
				}
				if(!$no_vehicle_url)
				{
					$page_url[] = $url_year;
					$page_url[] = $url_make;
				}
				if($_SESSION['fitment']['ModelName'])
				{
					$url_model = strtolower(str_replace(' ','-',trim($_SESSION['fitment']['ModelName'])));
					if(in_array($url_model,$page_url))
					{
						array_splice($page_url,array_search($url_model,$page_url),1);
					}
					if(!$no_vehicle_url)
					{
						$page_url[] = $url_model;
					}
				}
			}
			if(is_array($search_params) && sizeof($search_params))
			{
				foreach($search_params as $key => $value)
				{
					if(is_array($value))
					{
						$page_url[] = rawurlencode($key);
						foreach($value as $subvalue)
						{
							if(!is_array($subvalue) && !is_object($subvalue))
							{
								$page_url[] = rawurlencode($subvalue);
							}
						}
					}
					elseif(!is_object($value))
					{
						$page_url[] = rawurlencode($key);
						$page_url[] = rawurlencode($value);
					}
				}
			}
			if(is_array($search_values) && sizeof($search_values))
			{
				foreach($search_values as $value)
				{
					if(is_array($value))
					{
						foreach($value as $subvalue)
						{
							if(!is_array($subvalue) && !is_object($subvalue))
							{
								$page_url[] = rawurlencode($subvalue);
							}
						}
					}
					elseif(!is_object($value))
					{
						$page_url[] = rawurlencode($value);
					}
				}
			}
		}
		else
		{
			if(is_array($search_params) && sizeof($search_params))
			{
				$query_params = array_merge($query_params,$search_params);
			}
			if(is_array($search_values) && sizeof($search_values))
			{
				$query_params = array_merge($query_params,$search_values);
			}
			if($d && $dt && !$site_default)
			{
				$query_params['d'] = $d;
				$query_params['dt'] = $dt;
			}
		}
		if($language_url)
		{
			array_unshift($page_url,$language_url);
		}
		$combined_url = preg_replace('/\/\/+/','/',"/{$siteInformation['menubase']}/".implode('/',$page_url));
		if($language_url && sizeof($page_url) == 1)
		{
			$combined_url .= '/';
		}
		if(sizeof($query_params))
		{
			$combined_url .= '?'.http_build_query($query_params);
		}
		$buildURLCache[$serialized_params] = $combined_url;
		return $combined_url;
	}
}

####################################################
## Parsing a vehicle out of the URL
####################################################
function mod_urlVehicleSession()
{
	global $d, $dt;
	global $core;
	global $urlVehicleSessionRun;
	global $siteInformation;
	if($dt == 80 && !$urlVehicleSessionRun) // Don't want this running on non-ePIM_Category pages
	{
		global $myModules;
		$ePIM = $myModules->modules['ePIM'];
		$parsedURL = mod_parseURL();
		$pageURL = $parsedURL['page_url'];
		$fitmentSession = $_SESSION['fitment'];
		if(sizeof($parsedURL['url_params']) >= 2)
		{
			list($YearID,$MakeName,$ModelName) = $parsedURL['url_params'];
			if(preg_match('/^[0-9]{4}$/',$YearID) && strlen($MakeName))
			{
				if($YearID == $fitmentSession['YearID'] && strtolower($MakeName) == str_replace(' ','-',strtolower(trim($fitmentSession['MakeName']))) && strtolower($ModelName) == str_replace(' ','-',strtolower(trim($fitmentSession['ModelName']))))
				{
					$MakeID = $fitmentSession['MakeID'];
					$ModelID = $fitmentSession['ModelID'];
				}
				else
				{
					list($MakeID) = $ePIM->epimDB->queryRow("select MakeID from FitmentVehicle where YearID='$YearID' and replace(Make,' ','-')='$MakeName' and WebActive='1'");
					if($MakeID)
					{
						if(strlen($ModelName))
						{
							list($ModelID) = $ePIM->epimDB->queryRow("select ModelID from FitmentVehicle where YearID='$YearID' and MakeID='$MakeID' and replace(Model,' ','-')='$ModelName' and WebActive='1'");
						}
						if(!$ModelID)
						{
							$ModelID = 'ALL';
						}
					}
				}
			}
		}
		if($pageURL && $YearID && $MakeID && $ModelID)
		{
			if($YearID != $fitmentSession['YearID'] || $MakeID != $fitmentSession['MakeID'] || $ModelID != $fitmentSession['ModelID'])
			{
				ob_start();
				$ePIM->epimFitmentSession(array('YearID'=>$YearID,'MakeID'=>$MakeID,'ModelID'=>$ModelID));
				ob_end_clean();
			}
		}
		elseif($pageURL)
		{
			if(
				(
					$fitmentSession['YearID'] ||
					$fitmentSession['MakeID'] ||
					$fitmentSession['ModelID']
				)
			)
			{
				// $ePIM->epimClearFitmentSession();
			}
			// if(sizeof($parsedURL['url_params']))
			// {
			// 	$redirectURL = mod_buildURL(array(
			// 		'd'=>$d,
			// 		'dt'=>$dt,
			// 		'page_url'=>$pageURL
			// 	));
			// 	header("location: {$siteInformation['baseurl']}{$redirectURL}");
			// 	exit;
			// }
		}
		$urlVehicleSessionRun = true;
	}
}

####################################################
## Post-processing of pageContentList return,
## using shortcodes to customize content
## on certain types of pages
####################################################
function mod_pageContentList($contentList=array())
{
	if(isset($contentList['pageContent']))
	{
		if($contentList['pageContent']['tableName'] == 'ePIM_Category')
		{
			$contentList = mod_vehicleShortCodes($contentList);
		}
		elseif($contentList['pageContent']['tableName'] == 'ePIM_ProductSearch')
		{
			$contentList = mod_productSearchShortCodes($contentList);
		}
		elseif($contentList['pageContent']['tableName'] == 'pageSecure')
		{
			$contentList = mod_memberShortCodes($contentList);
		}
	}
	return $contentList;
}




function mod_vehicleShortCodes($content='')
{
	if(is_array($content))
	{
		foreach($content as $key => $value)
		{
			$content[$key] = mod_vehicleShortCodes($value);
		}
	}
	elseif(is_string($content) && strlen($content))
	{
		$year = $_SESSION['fitment']['YearID'];
		$make = $_SESSION['fitment']['MakeName'];
		$model = mod_ModelAliasName($_SESSION['fitment']['ModelName'],$year,$_SESSION['fitment']['ModelID']);
		$content = str_ireplace(array('','',''),array($year,$make,$model),$content);
		if($year && $make)
		{
			$content = preg_replace('/{if vehicle}(.*){else}(.*){endif}/i','$1',$content);
			$content = preg_replace('/{if vehicle}(.*){endif}/i','$1',$content);
		}
		else
		{
			$content = preg_replace('/{if vehicle}(.*){else}(.*){endif}/i','$2',$content);
			$content = preg_replace('/{if vehicle}.*{endif}/i','',$content);
		}
		$content = preg_replace('/\s\s+/',' ',$content);
	}
	return $content;
}





function mod_productSearchShortCodes($content='')
{
	if(is_array($content))
	{
		foreach($content as $key => $value)
		{
			$content[$key] = mod_productSearchShortCodes($value);
		}
	}
	elseif(is_string($content) && strlen($content))
	{
		$parsedURL = mod_parseURL();
		$Keyword = getSafeFormValue('find') ?: getSafeFormValue('Keyword');
		$content = str_ireplace(array(''), array($Keyword), $content);
		if($Keyword)
		{
			$content = preg_replace('/{if keyword}(.*){else}(.*){endif}/i','$1',$content);
			$content = preg_replace('/{if keyword}(.*){endif}/i','$1',$content);
		}
		else
		{
			$content = preg_replace('/{if keyword}(.*){else}(.*){endif}/i','$2',$content);
			$content = preg_replace('/{if keyword}.*{endif}/i','',$content);
		}
	}
	return $content;
}

function mod_partSearchShortCodes($content='')
{
	if(is_array($content))
	{
		foreach($content as $key => $value)
		{
			$content[$key] = mod_partSearchShortCodes($value);
		}
	}
	elseif(is_string($content) && strlen($content))
	{
		$parsedURL = mod_parseURL();
		$PartNumber = $parsedURL['url_params'][0] ?: getSafeFormValue('PartNumber');
		$content = str_ireplace(array(''),array($PartNumber),$content);
		if($PartNumber)
		{
			$content = preg_replace('/{if search}(.*){else}(.*){endif}/i','$1',$content);
			$content = preg_replace('/{if search}(.*){endif}/i','$1',$content);
		}
		else
		{
			$content = preg_replace('/{if search}(.*){else}(.*){endif}/i','$2',$content);
			$content = preg_replace('/{if search}.*{endif}/i','',$content);
		}
	}
	return $content;
}

function mod_memberShortCodeList()
{
	global $myModules;
	global $memberShortCodes;
	$Member = $myModules->modules['Member'];
	if(!is_array($memberShortCodes))
	{
		$fname = '';
		$fname_possessive = '';
		$lname = '';
		$lname_possessive = '';
		$name = '';
		$name_possessive = '';
		if($Member->member_id)
		{
			$memberList = $Member->memberList(array('member_id'=>$Member->member_id));
			$fname = $memberList[0]['fname'];
			$fname_possessive = strtolower(substr($fname,-1)) == 's' ? $fname."'" : $fname."'s";
			$lname = $memberList[0]['lname'];
			$lname_possessive = strtolower(substr($lname,-1)) == 's' ? $lname."'" : $lname."'s";
			$name = trim("$fname $lname");
			$name_possessive = strtolower(substr($name,-1)) == 's' ? $name."'" : $name."'s";
		}
		$memberShortCodes = array();
		$memberShortCodes[''] = $name_possessive;
		$memberShortCodes[''] = $name;
		$memberShortCodes[''] = $fname_possessive;
		$memberShortCodes[''] = $fname;
		$memberShortCodes[''] = $lname_possessive;
		$memberShortCodes[''] = $lname;
	}
	return $memberShortCodes;
}

function mod_memberShortCodes($content='')
{
	if(is_array($content))
	{
		foreach($content as $key => $value)
		{
			$content[$key] = mod_memberShortCodes($value);
		}
	}
	elseif(is_string($content) && strlen($content))
	{
		global $myModules;
		$Member = $myModules->modules['Member'];
		$memberShortCodes = mod_memberShortCodeList();
		$content = str_ireplace(array_keys($memberShortCodes),array_values($memberShortCodes),$content);
		if($Member->memberCheckLogin($_SESSION['member_sid']))
		{
			$content = preg_replace('/{if loggedin}(.*){else}(.*){endif}/i','$1',$content);
			$content = preg_replace('/{if loggedin}(.*){endif}/i','$1',$content);
		}
		else
		{
			$content = preg_replace('/{if loggedin}(.*){else}(.*){endif}/i','$2',$content);
			$content = preg_replace('/{if loggedin}.*{endif}/i','',$content);
		}
	}
	return $content;
}

####################################################
## Converting a string to a friendly URL segment
####################################################
function mod_urlComponent($value = '', $keepCase = false)
{
	if(strlen($value))
	{
		if(!$keepCase)
		{
			$value = strtolower($value);
		}
		$value = preg_replace('/\s?&\s?/', ' and ', $value);
		$value = preg_replace('/"([^\s"](.*[^\s"])?)"/', '$1', $value);
		$value = preg_replace('/\'([^\s\'](.*[^\s\'])?)\'/', '$1', $value);
		$value = preg_replace('/([0-9]+)\'/', '$1ft', $value);
		$value = preg_replace('/([0-9]+)"/', '$1in', $value);
		$value = preg_replace('/([a-zA-Z])\./', '$1', $value);
		$value = preg_replace('/[^a-zA-Z0-9-]/', '-', $value);
		$value = preg_replace('/--+/', '-', $value);
		$value = trim($value, '-');
	}
	return $value;
}


####################################################
## The list of fitment fields to use for selectors
####################################################
function mod_fitmentSelectorList($params=array())
{
	$requiredOnly = getParams('requiredOnly',$params);
	$optionalOnly = getParams('optionalOnly',$params);
	$key = getParams('key',$params);
	$fitmentSelectorList = array();
	if(!$optionalOnly)
	{
		$fitmentSelectorList[] = array(
			'searchField' => 'YearID',
			'nameField' => 'YearID',
			'label' => 'Year'
		);
		$fitmentSelectorList[] = array(
			'searchField' => 'MakeID',
			'nameField' => 'Make',
			'label' => 'Make'
		);
		$fitmentSelectorList[] = array(
			'searchField' => 'ModelID',
			'nameField' => 'Model',
			'label' => 'Model',
			'allowAll' => 0
		);
	}
	if(!$requiredOnly)
	{
		$fitmentSelectorList[] = array(
			'searchField' => 'SubmodelID',
			'nameField' => 'SubModel',
			'label' => 'Submodel',
			'optional' => 1
		);
		$fitmentSelectorList[] = array(
			'searchField' => 'BedLengthID',
			'nameField' => 'BedLength',
			'label' => 'Bed Length',
			'optional' => 1
		);
		$fitmentSelectorList[] = array(
			'searchField' => 'BodyNumDoorsID',
			'nameField' => 'BodyNumDoors',
			'label' => 'No. of Doors',
			'altQueryKey' => 'doors',
			'optional' => 1
		);
		$fitmentSelectorList[] = array(
			'searchField' => 'BedTypeID',
			'nameField' => 'BedTypeName',
			'label' => 'Bed Type',
			'optional' => 1
		);
		$fitmentSelectorList[] = array(
			'searchField' => 'FitmentBodyID',
			'nameField' => 'FitmentBody',
			'label' => 'Body Style',
			'optional' => 1
		);
		$fitmentSelectorList[] = array(
			'searchField' =>  'FuelTypeID',
			'nameField' => 'FuelTypeName',
			'label' => 'Fuel Type',
			'optional' => 1
		);

	}
	if($key && sizeof($fitmentSelectorList) && array_key_exists($key,$fitmentSelectorList[0]))
	{
		$selectorListUngrouped = $fitmentSelectorList;
		$fitmentSelectorList = array();
		foreach($selectorListUngrouped as $selector)
		{
			$fitmentSelectorList[$selector[$key]] = $selector;
		}
	}
	return $fitmentSelectorList;
}

####################################################
## Formatting fitment display name, including
## custom logic for certain fields
####################################################
function mod_fitmentDisplayName( $input_string, $input_value )
{
	$return_display_string = $input_string;
	$return_display_value = $input_value;

	if ( $input_string === 'BedLength' )
	{
		$return_display_string = 'Bed Length';

		if ( ! $input_value )
		{
			return;
		}

		$totalInches = ( float ) $input_value;
		if($totalInches > 12)
		{
			$feet = floor($totalInches/12);
			$inches = ($totalInches%12);
			$return_display_value = $feet."'";
			if($inches > 1)
			{
				$return_display_value .= " ".$inches."\"";
			}
		}
	}
	if ( $input_string === 'BedTypeName' )
	{
		$return_display_string = 'Bed Type';
	}
	return [$return_display_string, $return_display_value];
}

####################################################
## Generate the URL for the "Continue Shopping"
## button in the checkout, including vehicle
####################################################
function mod_eicpContinueShoppingURL()
{
	$pageContentList = pageContentList('','','',array('d'=>1,'dt'=>80,'contentOnly'=>1));
	$url = mod_buildURL($pageContentList['pageContent']);
	return $url;
}


####################################################
## Extract and organize the filters
## from a category's child elements
####################################################
function mod_getCategoryFilters($params = array())
{
	global $myModules;
	$ePIM = $myModules->modules['ePIM'];
	$eICP = $myModules->modules['eICP'];

	$pageContentList = getParams('pageContentList', $params) ?: array();
	$selectedFilterURL = getParams('selectedFilterURL', $params) ?: array();
	if($selectedFilterURL && !is_array($selectedFilterURL))
	{
		$selectedFilterURL = explode(',', $selectedFilterURL);
	}
	if(sizeof($selectedFilterURL))
	{
		$selectedBrandAAIAID = array();
		$selectedProductAttributeID = array();
		$selectedPriceRange = array();
	}
	else
	{
		$selectedBrandAAIAID = getParams('selectedBrandAAIAID', $params) ?: array();
		if($selectedBrandAAIAID && !is_array($selectedBrandAAIAID))
		{
			$selectedBrandAAIAID = explode(',', $selectedBrandAAIAID);
		}
		$selectedProductAttributeID = getParams('selectedProductAttributeID', $params) ?: array();
		if($selectedProductAttributeID && !is_array($selectedProductAttributeID))
		{
			$selectedProductAttributeID = explode(',', $selectedProductAttributeID);
		}
		$selectedPriceRange = getParams('selectedPriceRange', $params) ?: array();
	}

	$filterBasePageContent = array();
	$filterContentFull = array();
	$filterURLList = array();
	$selectedFilterContent = array();
	$selectedFilterContentList = array();
	$selectedFilterType = NULL;
	$selectedFilterText = array('filter'=>'', 'brand'=>'', 'attribute'=>'');
	$filteredBrandAAIAIDList = array();
	$filteredProductAttributeIDList = array();
	$filteredPriceRangeList = array();
	if($pageContentList['pageContent']['data_table_name'] == 'ePIM_Category')
	{
		$filterBasePageContent = $pageContentList['pageContent'];

		$autoFilters = $filterBasePageContent['autoFilters'];
		$autoBrandFilters = in_array($autoFilters, array('brand', 'brand_attribute'));
		$autoAttributeFilters = in_array($autoFilters, array('attribute', 'brand_attribute'));

		$brandPageList = array();
		$attributePageList = array();
		$brandFilterList = array();
		$attributeFilterList = array();

		if(is_array($pageContentList['subOus']) && sizeof($pageContentList['subOus']))
		{
			foreach($pageContentList['subOus'] as $item)
			{
				if($item['pageContent']['data_table_name'] == 'ePIM_BrandPage')
				{
					$BrandAAIAID = trim($item['pageContent']['BrandAAIAID']);
					$url = trim($item['pageContent']['url']);
					if($BrandAAIAID && $url)
					{
						if(!isset($brandPageList[$BrandAAIAID]) && !isset($brandFilterList[$BrandAAIAID]) && !in_array($url, $filterURLList))
						{
							$brandFilterList[$BrandAAIAID] = $item['pageContent'];
							$filterURLList[] = $url;
						}
					}
				}
				if($item['pageContent']['data_table_name'] == 'ePIM_AttributePage')
				{
					$ProductAttributeID = trim($item['pageContent']['ProductAttributeID']);
					$url = trim($item['pageContent']['url']);
					if($ProductAttributeID && $url)
					{
						if(!isset($attributePageList[$ProductAttributeID]) && !isset($attributeFilterList[$ProductAttributeID]) && !in_array($url, $filterURLList))
						{
							$attributeFilterList[$ProductAttributeID] = $item['pageContent'];
							$filterURLList[] = $url;
						}
					}
				}
			}
		}

		if(is_array($pageContentList['subContent']) && sizeof($pageContentList['subContent']))
		{
			foreach($pageContentList['subContent'] as $item)
			{
				if($item['data_table_name'] == 'ePIM_BrandFilter')
				{
					$BrandAAIAID = trim($item['BrandAAIAID']);
					$url = trim($item['url']);
					if($BrandAAIAID && $url)
					{
						if(!isset($brandPageList[$BrandAAIAID]) && !isset($brandFilterList[$BrandAAIAID]) && !in_array($url, $filterURLList))
						{
							$brandFilterList[$BrandAAIAID] = $item;
							$filterURLList[] = $url;
						}
					}
				}
				if($item['data_table_name'] == 'ePIM_AttributeFilter')
				{
					$ProductAttributeID = trim($item['ProductAttributeID']);
					$url = trim($item['url']);
					if($ProductAttributeID && $url)
					{
						if(!isset($attributePageList[$ProductAttributeID]) && !isset($attributeFilterList[$ProductAttributeID]) && !in_array($url, $filterURLList))
						{
							$attributeFilterList[$ProductAttributeID] = $item;
							$filterURLList[] = $url;
						}
					}
				}
			}
		}

		$ordinal = 0;

		$BrandAAIAIDList = array_merge(array_keys($brandPageList), array_keys($brandFilterList));
		$brandPages = array();
		$brandFilters = array();
		if(sizeof($BrandAAIAIDList) || $autoBrandFilters)
		{
			$itemBrandIDList = $ePIM->p->epimProductList(array(
				'CategoryID'=>$filterBasePageContent['id'],
				'searchSubcategories'=>1,
				'column'=>'BrandAAIAID'
			));
			if(sizeof($itemBrandIDList))
			{
				$brandList = $ePIM->brandList(array(
					'BrandID'=>$itemBrandIDList,
					'distinctBrand'=>1
				));
				if(sizeof($brandList))
				{
					foreach($brandList as $brand)
					{
						$BrandAAIAID = $brand['BrandID'];
						if(in_array($BrandAAIAID, $BrandAAIAIDList) || $autoBrandFilters)
						{
							$overrideBrandName = mod_getBrandNameOverride($BrandAAIAID);
							if($overrideBrandName)
							{
								$brand['BrandName'] = $overrideBrandName;
							}
						}
						$BrandName = $brand['BrandName'];
						$url = '';
						$brandFilterContent = NULL;
						if(array_key_exists($BrandAAIAID, $brandFilterList))
						{
							$brandPageContent = $brandFilterList[$BrandAAIAID];
							$ordinal++;
							$brandPageContent['ordinal'] = $ordinal;
							$url = $brandPageContent['url'];
							$brandFilterContent = array(
								'pageContent'=>$brandPageContent,
								'brandContent'=>$brand
							);
							$filteredBrandAAIAIDList[] = $BrandAAIAID;
						}
						elseif(array_key_exists($BrandAAIAID, $brandPageList))
						{
							$brandPageContent = $brandPageList[$BrandAAIAID];
							$ordinal++;
							$brandPageContent['ordinal'] = $ordinal;
							$brandFilterContent = array(
								'pageContent'=>$brandPageContent,
								'brandContent'=>$brand
							);
							$filteredBrandAAIAIDList[] = $BrandAAIAID;
						}
						elseif($autoBrandFilters && $BrandName)
						{
							$url = mod_urlComponent($BrandName);
							if(!in_array($url, $filterURLList))
							{
								$brandPageContent = array();
								$brandPageContent['data_table_name'] = 'ePIM_BrandFilter_AUTO';
								$brandPageContent['tableName'] = 'ePIM_BrandFilter_AUTO';
								$brandPageContent['BrandAAIAID'] = $BrandAAIAID;
								$brandPageContent['url'] = $url;
								$ordinal++;
								$brandPageContent['ordinal'] = $ordinal;
								$brandFilterContent = array(
									'pageContent'=>$brandPageContent,
									'brandContent'=>$brand
								);
								$filterURLList[] = $url;
								$filteredBrandAAIAIDList[] = $BrandAAIAID;
							}
						}
						if($brandFilterContent)
						{
							if($url)
							{
								$brandFilters[$url] = $brandFilterContent;
							}
							else
							{
								$brandPages[] = $brandFilterContent;
							}
							if(in_array($brandFilterContent['pageContent']['BrandAAIAID'], $selectedBrandAAIAID))
							{
								$selectedFilterContentList[] = $brandFilterContent;
								if($url)
								{
									$selectedFilterURL[] = $url;
								}
								if(!$selectedFilterType)
								{
									$selectedFilterType = 'brand';
								}
							}
						}
					}
				}
			}
		}

		$ProductAttributeIDList = array_merge(array_keys($attributePageList), array_keys($attributeFilterList));
		$attributePages = array();
		$attributeFilters = array();
		if(sizeof($ProductAttributeIDList) || $autoAttributeFilters)
		{
			$productAttributeQualifierList = $ePIM->p->productAttributeQualifierList(array(
				'CategoryID'=>$filterBasePageContent['id'],
				'searchSubcategories'=>1
			));
			if(sizeof($productAttributeQualifierList))
			{
				foreach($productAttributeQualifierList as $qualifier)
				{
					foreach($qualifier['ProductAttributeList'] as $attribute)
					{
						$ProductAttributeID = $attribute['ProductAttributeID'];
						$ProductAttribute = $attribute['ProductAttribute'];
						$url = '';
						$attributeFilterContent = NULL;
						if(array_key_exists($ProductAttributeID, $attributeFilterList))
						{
							$attributePageContent = $attributeFilterList[$ProductAttributeID];
							$ordinal++;
							$attributePageContent['ordinal'] = $ordinal;
							$url = $attributePageContent['url'];
							$attributeFilterContent = array(
								'pageContent'=>$attributePageContent,
								'attributeContent'=>$attribute
							);
							$filteredProductAttributeIDList[] = $ProductAttributeID;
						}
						elseif(array_key_exists($ProductAttributeID, $attributePageList))
						{
							$attributePageContent = $attributePageList[$ProductAttributeID];
							$ordinal++;
							$attributePageContent['ordinal'] = $ordinal;
							$attributeFilterContent = array(
								'pageContent'=>$attributePageContent,
								'attributeContent'=>$attribute
							);
							$filteredProductAttributeIDList[] = $ProductAttributeID;
						}
						elseif($autoAttributeFilters && $ProductAttribute)
						{
							$url = mod_urlComponent($ProductAttribute);
							if(!in_array($url, $filterURLList))
							{
								$attributePageContent = array();
								$attributePageContent['data_table_name'] = 'ePIM_AttributeFilter_AUTO';
								$attributePageContent['tableName'] = 'ePIM_AttributeFilter_AUTO';
								$attributePageContent['ProductAttributeID'] = $ProductAttributeID;
								$attributePageContent['url'] = $url;
								$ordinal++;
								$attributePageContent['ordinal'] = $ordinal;
								$attributeFilterContent = array(
									'pageContent'=>$attributePageContent,
									'attributeContent'=>$attribute
								);
								$filterURLList[] = $url;
								$filteredProductAttributeIDList[] = $ProductAttributeID;
							}
						}
						if($attributeFilterContent)
						{
							if($url)
							{
								$attributeFilters[$url] = $attributeFilterContent;
							}
							else
							{
								$attributePages[] = $attributeFilterContent;
							}
							if(in_array($attributeFilterContent['pageContent']['ProductAttributeID'], $selectedProductAttributeID))
							{
								$selectedFilterContentList[] = $attributeFilterContent;
								if($url)
								{
									$selectedFilterURL[] = $url;
								}
								if(!$selectedFilterType)
								{
									$selectedFilterType = 'attribute';
								}
							}
						}
					}
				}
			}
		}

		$filterPriceRanges = trim($filterBasePageContent['filterPriceRanges']);
		$priceFilters = array();
		if($filterPriceRanges && method_exists($ePIM, 'cartPriceRange'))
		{
			$filterPriceRanges = preg_replace('/[^0-9\.;\|]/', '', $filterPriceRanges);
			if($filterPriceRanges)
			{
				$cartPriceRange = $ePIM->cartPriceRange(array(
					'CategoryID'=>$filterBasePageContent['id'],
					'searchSubcategories'=>1,
					'multipleItemPriceTypes'=>1,
					'PriceList'=>1,
					'round'=>'up'
				));
				if(sizeof($cartPriceRange['Price']['PriceList']))
				{
					$eICP->setCurrency(array('currency'=>'USD'));
					$filterPriceRanges = preg_replace('/([0-9]+)(\.[0-9]*)+/', '$1', $filterPriceRanges);
					$filterPriceRanges = preg_split('/[;\|]+/', $filterPriceRanges);
					$filterPriceRanges = array_unique($filterPriceRanges);
					sort($filterPriceRanges, SORT_NUMERIC);
					if(in_array('0', $filterPriceRanges))
					{
						array_shift($filterPriceRanges);
					}
					$minPrice = $filterPriceRanges[0];
					$minPriceFormatted = money_format('%.0n', $minPrice);
					$maxPrice = $minPrice - 1;
					$maxPriceDisplay = "Under $minPriceFormatted";
					$maxPriceURL = "price-under-{$minPrice}";
					if($cartPriceRange['Price']['PriceMin'] <= $maxPrice)
					{
						$maxPriceContent = array();
						$maxPriceContent['pageContent']['data_table_name'] = 'ePIM_PriceFilter_AUTO';
						$maxPriceContent['pageContent']['tableName'] = 'ePIM_PriceFilter_AUTO';
						$maxPriceContent['pageContent']['PriceMin'] = 0;
						$maxPriceContent['pageContent']['PriceMax'] = $maxPrice;
						$maxPriceContent['pageContent']['PriceRange'] = "0-$maxPrice";
						$maxPriceContent['pageContent']['PriceDisplay'] = $maxPriceDisplay;
						$maxPriceContent['pageContent']['PriceFilterText'] = $maxPriceDisplay;
						$maxPriceContent['pageContent']['url'] = $maxPriceURL;
						$ordinal++;
						$maxPriceContent['pageContent']['ordinal'] = $ordinal;
						$priceFilters[$maxPriceURL] = $maxPriceContent;
						$filterURLList[] = $maxPriceURL;
						$filteredPriceRangeList[] = $maxPriceContent['pageContent']['PriceRange'];
						if($maxPriceContent['pageContent']['PriceRange'] == $selectedPriceRange['PriceRange'])
						{
							$selectedFilterContentList[] = $maxPriceContent;
							$selectedFilterURL[] = $maxPriceURL;
							if(!$selectedFilterType)
							{
								$selectedFilterType = 'price';
							}
						}
					}
					while(sizeof($filterPriceRanges))
					{
						$minPrice = array_shift($filterPriceRanges);
						$minPriceFormatted = money_format('%.0n', $minPrice);
						if(sizeof($filterPriceRanges))
						{
							$maxPrice = $filterPriceRanges[0];
							$maxPrice--;
							$maxPriceFormatted = money_format('%.0n', $maxPrice);
							$priceDisplay = "{$minPriceFormatted} to {$maxPriceFormatted}";
							$priceFilterText = "Between {$minPriceFormatted} and {$maxPriceFormatted}";
							$priceURL = "price-{$minPrice}-to-{$maxPrice}";
							$priceRange = range($minPrice, $maxPrice);
							$includePriceFilter = sizeof(array_intersect($priceRange, $cartPriceRange['Price']['PriceList']));
						}
						else
						{
							$maxPrice = 0;
							$priceDisplay = "{$minPriceFormatted}+";
							$priceFilterText = "Over {$minPriceFormatted}";
							$priceURL = "price-{$minPrice}-plus";
							$includePriceFilter = ($cartPriceRange['Price']['PriceMax'] >= $minPrice);
						}
						if($includePriceFilter)
						{
							$priceContent = array();
							$priceContent['pageContent']['data_table_name'] = 'ePIM_PriceFilter_AUTO';
							$priceContent['pageContent']['tableName'] = 'ePIM_PriceFilter_AUTO';
							$priceContent['pageContent']['PriceMin'] = $minPrice;
							$priceContent['pageContent']['PriceMax'] = $maxPrice;
							$priceContent['pageContent']['PriceRange'] = "$minPrice-$maxPrice";
							$priceContent['pageContent']['PriceDisplay'] = $priceDisplay;
							$priceContent['pageContent']['PriceFilterText'] = $priceFilterText;
							$priceContent['pageContent']['url'] = $priceURL;
							$ordinal++;
							$priceContent['pageContent']['ordinal'] = $ordinal;
							$priceFilters[$priceURL] = $priceContent;
							$filterURLList[] = $priceURL;
							$filteredPriceRangeList[] = $priceContent['pageContent']['PriceRange'];
							if($priceContent['pageContent']['PriceRange'] == $selectedPriceRange['PriceRange'])
							{
								$selectedFilterContentList[] = $priceContent;
								$selectedFilterURL[] = $priceURL;
								if(!$selectedFilterType)
								{
									$selectedFilterType = 'price';
								}
							}
						}
					}
				}
			}
		}

		$filterURLList = array_merge(array_keys($brandFilters), array_keys($attributeFilters), array_keys($priceFilters));
		if(sizeof($selectedFilterURL))
		{
			$selectedFilterURL = array_intersect($filterURLList, $selectedFilterURL);
		}

		$selectedFilterText['filter'] = array();
		$selectedFilterText['notbrand'] = array();
		$selectedFilterText['attribute'] = array();
		$selectedFilterText['notattribute'] = array();
		$selectedFilterText['notprice'] = array();

		if(sizeof($selectedFilterURL) && empty($selectedBrandAAIAID) && empty($selectedProductAttributeID) && empty($selectedPriceRange))
		{
			foreach($selectedFilterURL as $url)
			{
				if(isset($brandFilters[$url]))
				{
					$filterContent = $brandFilters[$url];
					$selectedFilterContentList[] = $filterContent;
					$selectedBrandAAIAID[] = $filterContent['pageContent']['BrandAAIAID'];
					$selectedFilterText['filter'][] = trim($filterContent['brandContent']['BrandName']);
					$selectedFilterText['brand'] = trim($filterContent['brandContent']['BrandName']);
					$selectedFilterText['notattribute'][] = trim($filterContent['brandContent']['BrandName']);
					$selectedFilterText['notprice'][] = trim($filterContent['brandContent']['BrandName']);
					if(!$selectedFilterType)
					{
						$selectedFilterType = 'brand';
					}
				}
				elseif(isset($attributeFilters[$url]))
				{
					$filterContent = $attributeFilters[$url];
					$selectedFilterContentList[] = $filterContent;
					$selectedProductAttributeID[] = $filterContent['pageContent']['ProductAttributeID'];
					$selectedFilterText['filter'][] = trim($filterContent['attributeContent']['ProductAttribute']);
					$selectedFilterText['attribute'][] = trim($filterContent['attributeContent']['ProductAttribute']);
					$selectedFilterText['notbrand'][] = trim($filterContent['attributeContent']['ProductAttribute']);
					$selectedFilterText['notprice'][] = trim($filterContent['attributeContent']['ProductAttribute']);
					if(!$selectedFilterType)
					{
						$selectedFilterType = 'attribute';
					}
				}
				elseif(isset($priceFilters[$url]))
				{
					$filterContent = $priceFilters[$url];
					$selectedFilterContentList[] = $filterContent;
					$selectedPriceRange['PriceMin'] = $filterContent['pageContent']['PriceMin'];
					$selectedPriceRange['PriceMax'] = $filterContent['pageContent']['PriceMax'];
					$selectedPriceRange['PriceRange'] = $filterContent['pageContent']['PriceRange'];
					$selectedFilterText['filter'][] = trim($filterContent['pageContent']['PriceDisplay']);
					$selectedFilterText['price'] = trim($filterContent['pageContent']['PriceDisplay']);
					$selectedFilterText['pricealt'] = trim($filterContent['pageContent']['PriceFilterText']);
					$selectedFilterText['notattribute'][] = trim($filterContent['pageContent']['PriceDisplay']);
					$selectedFilterText['notbrand'][] = trim($filterContent['pageContent']['PriceDisplay']);
					if(!$selectedFilterType)
					{
						$selectedFilterType = 'price';
					}
				}
			}
		}

		if(sizeof($selectedFilterContentList))
		{
			$selectedFilterContent = $selectedFilterContentList[0];
		}

		$selectedFilterText['filter'] = implode(' ', $selectedFilterText['filter']);
		$selectedFilterText['notbrand'] = implode(' ', $selectedFilterText['notbrand']);
		$selectedFilterText['attribute'] = implode(' ', $selectedFilterText['attribute']);
		$selectedFilterText['notattribute'] = implode(' ', $selectedFilterText['notattribute']);
		$selectedFilterText['notprice'] = implode(' ', $selectedFilterText['notprice']);

		if(sizeof($selectedBrandAAIAID) || sizeof($selectedProductAttributeID) || $selectedPriceRange['PriceMin'] || $selectedPriceRange['PriceMax'])
		{
			$itemParams = array();
			$itemParams['CategoryID'] = $filterBasePageContent['id'];
			$itemParams['searchSubcategories'] = 1;
			if(sizeof($selectedBrandAAIAID))
			{
				$itemParams['BrandAAIAID'] = $selectedBrandAAIAID;
			}
			if(sizeof($selectedProductAttributeID))
			{
				$itemParams['ProductAttributeID'] = $selectedProductAttributeID;
			}
			if($selectedPriceRange['PriceMin'])
			{
				$itemParams['PriceMin'] = $selectedPriceRange['PriceMin'];
				$itemParams['priceRound'] = 'up';
			}
			if($selectedPriceRange['PriceMax'])
			{
				$itemParams['PriceMax'] = $selectedPriceRange['PriceMax'];
				$itemParams['priceRound'] = 'up';
			}
			$itemParams['column'] = 'ItemID';
			$ItemIDList = $ePIM->p->epimProductList($itemParams);
			if($ItemIDList)
			{
				if(sizeof($filteredBrandAAIAIDList))
				{
					$intersectBrandAAIAIDList = $ePIM->p->epimProductList(array(
						'ItemID'=>$ItemIDList,
						'column'=>'BrandAAIAID'
					));
					$filteredBrandAAIAIDList = array_intersect($filteredBrandAAIAIDList, $intersectBrandAAIAIDList);
				}

				if(sizeof($filteredProductAttributeIDList))
				{
					$intersectProductAttributeIDList = array();
					$productAttributeQualifierList = $ePIM->p->productAttributeQualifierList(array(
						'itemIDList'=>$ItemIDList
					));
					if(sizeof($productAttributeQualifierList))
					{
						foreach($productAttributeQualifierList as $qualifier)
						{
							foreach($qualifier['ProductAttributeList'] as $attribute)
							{
								$intersectProductAttributeIDList[] = $attribute['ProductAttributeID'];
							}
						}
					}
					$filteredProductAttributeIDList = array_intersect($filteredProductAttributeIDList, $intersectProductAttributeIDList);
				}

				if(sizeof($filteredPriceRangeList) && method_exists($ePIM, 'cartPriceRange'))
				{
					$filteredCartPriceRange = $ePIM->cartPriceRange(array(
						'ItemID'=>$ItemIDList,
						'multipleItemPriceTypes'=>1,
						'PriceList'=>1,
						'round'=>'up'
					));
					$intersectPriceRangeList = array();
					if(sizeof($filteredCartPriceRange['Price']['PriceList']))
					{
						foreach($priceFilters as $priceContent)
						{
							$addToIntersectList = false;
							if($priceContent['pageContent']['PriceMin'] && $priceContent['pageContent']['PriceMax'])
							{
								$priceRange = range($priceContent['pageContent']['PriceMin'], $priceContent['pageContent']['PriceMax']);
								if(sizeof(array_intersect($priceRange, $filteredCartPriceRange['Price']['PriceList'])))
								{
									$addToIntersectList = true;
								}
							}
							elseif($priceContent['pageContent']['PriceMin'])
							{
								if($filteredCartPriceRange['Price']['PriceMax'] >= $priceContent['pageContent']['PriceMin'])
								{
									$addToIntersectList = true;
								}
							}
							elseif($priceContent['pageContent']['PriceMax'])
							{
								if($filteredCartPriceRange['Price']['PriceMin'] <= $priceContent['pageContent']['PriceMax'])
								{
									$addToIntersectList = true;
								}
							}
							if($addToIntersectList)
							{
								$intersectPriceRangeList[] = $priceContent['pageContent']['PriceRange'];
							}
						}
					}
					$filteredPriceRangeList = array_intersect($filteredPriceRangeList, $intersectPriceRangeList);
				}
			}
		}

		$brandFilters = array_values($brandFilters);
		$attributeFilters = array_values($attributeFilters);
		$priceFilters = array_values($priceFilters);
		$filterContentFull = array_merge($brandFilters, $brandPages, $attributeFilters, $attributePages, $priceFilters);
		if(sizeof($filterContentFull))
		{
			usort($filterContentFull, 'pageContentListSort');
		}
	}
	else
	{
		$selectedFilterURL = array();
	}

	return array(
		'filterBasePageContent'=>$filterBasePageContent,
		'filterContent'=>$filterContentFull,
		'filterURLList'=>$filterURLList,
		'selectedFilterURL'=>$selectedFilterURL,
		'selectedFilterContent'=>$selectedFilterContent,
		'selectedFilterContentList'=>$selectedFilterContentList,
		'selectedBrandAAIAID'=>$selectedBrandAAIAID,
		'selectedProductAttributeID'=>$selectedProductAttributeID,
		'selectedPriceRange'=>$selectedPriceRange,
		'selectedFilterType'=>$selectedFilterType,
		'selectedFilterText'=>$selectedFilterText,
		'filteredBrandAAIAIDList'=>$filteredBrandAAIAIDList,
		'filteredProductAttributeIDList'=>$filteredProductAttributeIDList,
		'filteredPriceRangeList'=>$filteredPriceRangeList
	);
}

function mod_getBrandNameOverride($BrandAAIAID = '')
{
	$BrandName = '';
	if($BrandAAIAID)
	{
		global $core;

		$sql = <<<SQL
select o.field_option_label
from eCMP_field_option o
inner join eCMP_field_option_group og on o.field_option_group_id = og.field_option_group_id
where og.field_option_group_name like 'AAIA Brand%'
and o.field_option_value = '$BrandAAIAID'
limit 1
SQL;

		list($BrandName) = $core->site->queryRow($sql);
	}
	return $BrandName;
}



function mod_getWistiaVideoData($params = array())
{
	$url = getParams('url', $params);
	$embedType = getParams('embedType', $params) ?: 'iframe';
	$width = getParams('width', $params) ?: 1280;
	$height = getParams('height', $params) ?: 720;

	$return = NULL;

	if(preg_match('/https?:\/\/(.+)?(wistia.com|wi.st)\/(medias|embed)\/.*/', $url))
	{
		$query = array();
		$query['embedType'] = $embedType;
		$query['width'] = $width;
		$query['height'] = $height;

		if(strstr($url, '?') !== false)
		{
			list($url) = explode('?', $url);
		}

		$url .= '?'.http_build_query($query);

		$wistiaOembedURL = 'http://fast.wistia.com/oembed.json?'.http_build_query(array('url'=>$url));

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $wistiaOembedURL);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$wistiaData = curl_exec($ch);
		curl_close($ch);

		if($wistiaData)
		{
			$return = json_decode($wistiaData);
		}
	}

	return $return;
}

####################################################
## Populate the gallery of a product line page
## from images in a target folder
####################################################
function mod_buildProductLineGallery($params = array())
{
	$bulk = getParams('bulk', $params);
	$return = array();
	if(($_SESSION['isAdmin'] && $_SESSION['editItems']) || $bulk)
	{
		global $myModules;
		global $siteInformation;
		$Content = $myModules->modules['Content'];

		$xid = getParams('xid', $params);
		$folder = getParams('folder', $params);
		if($xid && ($folder || $bulk))
		{
			$contentList = $Content->contentList(array('xid'=>$xid, 'data'=>1));
			if($contentList[0]['data_table_name'] == 'ePIM_Category')
			{
				$categoryLabel = trim($contentList[0]['content_label']);
				$categoryTitle = trim($contentList[0]['title']) ?: trim($contentList[0]['label']) ?: $categoryLabel;
				if(!$folder)
				{
					$folder = "/{$siteInformation['uploadDir']}/galleries/".mod_urlComponent($categoryTitle);
				}
				if(substr($folder, 0, strlen($siteInformation['uploadDir']) + 2) == "/{$siteInformation['uploadDir']}/")
				{
					$folderFull = str_replace('//', '/', $siteInformation['siteBaseDir'].$folder);
					if(file_exists($folderFull))
					{
						$imagePathList = glob("$folderFull/*.{jpg,png}", GLOB_BRACE);
						if(sizeof($imagePathList))
						{
							$galleryTypeList = $Content->typeList(array('tableName'=>'productSelector'));
							$galleryTypeID = $galleryTypeList[0]['id'];
							if($galleryTypeID)
							{
								$galleryItemTypeList = $Content->typeList(array('tableName'=>'galleryItem'));
								$galleryItemTypeID = $galleryItemTypeList[0]['id'];
								if($galleryItemTypeID)
								{
									$categoryContentList = $Content->contentList(array('parentXID'=>$xid));
									$galleryXID = NULL;
									if(sizeof($categoryContentList))
									{
										foreach($categoryContentList as $content)
										{
											if($content['data_table'] == $galleryTypeID)
											{
												$galleryXID = $content['xid'];
												break;
											}
										}
									}
									if(!$galleryXID)
									{
										$saveParams = array();
										$saveParams['parentXID'] = $xid;
										$saveParams['dt'] = $galleryTypeID;
										$saveParams['content_label'] = 'Gallery';
										$saveParams['active'] = 1;
										$saveResponse = json_decode($Content->contentSaveContent($saveParams));
										$galleryXID = $saveResponse->xid;
									}
									if($galleryXID)
									{
										$galleryContentList = $Content->contentList(array('parentXID'=>$galleryXID, 'data'=>1));
										$assignedImageList = array();
										if(sizeof($galleryContentList))
										{
											foreach($galleryContentList as $content)
											{
												if($content['data_table'] == $galleryItemTypeID && $content['image'])
												{
													$assignedImageList[] = $content['image'];
												}
											}
										}

										$imageList = array();
										foreach($imagePathList as $path)
										{
											$image = substr($path, strlen($siteInformation['siteBaseDir']));
											if(!preg_match('/^\//', $image))
											{
												$image = "/$image";
											}
											$imageList[] = $image;
										}

										$imageList = array_diff($imageList, $assignedImageList);
										if(sizeof($imageList))
										{
											$galleryItemCount = sizeof($assignedImageList);
											$galleryItemAddCount = 0;
											foreach($imageList as $image)
											{
												$galleryItemCount++;

												$saveParams = array();
												$saveParams['parentXID'] = $galleryXID;
												$saveParams['dt'] = $galleryItemTypeID;
												$saveParams['content_label'] = "Gallery Image $galleryItemCount";
												$saveParams['active'] = 1;
												$saveParams['image'] = $image;
												$saveParams['imageAlt'] = $categoryTitle;
												$saveParams['title'] = $categoryTitle;

												$saveResponse = json_decode($Content->contentSaveContent($saveParams));
												$galleryItemXID = $saveResponse->xid;
												if($galleryItemXID)
												{
													$galleryItemAddCount++;
												}
												else
												{
													$galleryItemCount--;
												}
											}
											if($galleryItemAddCount)
											{
												$imagePlural = $galleryItemAddCount == 1 ? 'image' : 'images';
												$return['status'] = 'success';
												$return['message'] = "$galleryItemAddCount $imagePlural added to gallery";
											}
											else
											{
												$return['status'] = 'fail';
												$return['message'] = 'Items could not be added to gallery';
											}
										}
										else
										{
											$return['status'] = 'fail';
											$return['message'] = "All images in $folder have already been assigned to the gallery on this page";
										}
									}
									else
									{
										$return['status'] = 'fail';
										$return['message'] = 'This page does not have an existing gallery element, and one could not be created';
									}
								}
								else
								{
									$return['status'] = 'fail';
									$return['message'] = 'Type not found: galleryItem';
								}
							}
							else
							{
								$return['status'] = 'fail';
								$return['message'] = 'Type not found: elementProductBenefits';
							}
						}
						else
						{
							$return['status'] = 'fail';
							$return['message'] = "There are no .jpg or .png files in $folder";
						}
					}
					else
					{
						$return['status'] = 'fail';
						$return['message'] = "The folder $folder does not exist";
					}
				}
				else
				{
					$return['status'] = 'fail';
					$return['message'] = "Specified folder path must start with /{$siteInformation['uploadDir']}/";
				}
			}
			else
			{
				$return['status'] = 'fail';
				$return['message'] = 'Specified page is not a category or product line';
			}
		}
		else
		{
			$return['status'] = 'fail';
			if(!$xid)
			{
				$return['message'] = 'No page specified to assign gallery images to';
			}
			elseif(!$folder && !$bulk)
			{
				$return['message'] = 'No image folder specified';
			}
		}
	}
	else
	{
		$return['status'] = 'fail';
		$return['message'] = 'You are not allowed to execute this function';
	}
	if(!$return['status'])
	{
		$return['status'] = 'fail';
	}
	return $return;
}

####################################################
## Populate the gallery of a product line page
## from images in a target folder
####################################################
function mod_setCategoryTileSort($params = array())
{
	$sort = getParams('sort', $params);
	if(in_array($sort, array('popularity', 'new', 'alpha', 'reverse_alpha', 'price', 'reverse_price')))
	{
		$_SESSION['categoryTiles']['sort'] = $sort;
	}
}


/*
####################################################
Obsolete functions, left in to avoid parse errors.
If this type of search functionality is needed in
future, it should be added to core or the
Content module.
####################################################
 */
function mod_siteSearchKeyword()
{
	fb_warn('mod_siteSearchKeyword');
	return false;
}
function mod_siteSearchResults()
{
	fb_warn('mod_siteSearchResults');
	return false;
}
function mod_search_exerpt()
{
	fb_warn('mod_search_exerpt');
	return false;
}
function mod_search_highlight()
{
	fb_warn('mod_search_highlight');
	return false;
}


/*
###################################################
This function is used specifically in the product selector,
but there are definitely more ways that it can be used.
Pretty much you input an array of matches that you want to use to replace a string,
	an array of strings that you want to match to, and the values (at the replace array index) that you want to replace with. You should get back an array of strings that have been replaced, that you can use a list() with to initialize variables. It's pretty cool.
###################################################
 */
function mod_megaStringReplace( $stringArray, $replaceArray, $replaceValueArray )
{
	$rArray = array();
	foreach( $stringArray as $s )
	{
		$rArray[] = str_replace($replaceArray, $replaceValueArray, $s );
	}
	return $rArray;
}

function mod_imgFileExists( $pathFromImgBase )
{
	global $siteInformation;
	$siteInfo = $siteInformation['siteBaseDir'];
	$replacedPath = str_replace('//', '/', $siteInfo . $pathFromImgBase);
	return file_exists($replacedPath);
}

function mod_getCategoryPriceRange($params = array())
{
	$CategoryID = getParams('CategoryID', $params);
	$searchSubcategories = getParams('searchSubcategories', $params);
	$numbers_only = getParams('numbersOnly', $params);
	$priceRange = array('min'=>0, 'max'=>0, 'formatted'=>'');
	if($CategoryID)
	{
		global $myModules;
		$ePIM = $myModules->modules['ePIM'];
		$eICP = $myModules->modules['eICP'];

		if(method_exists($ePIM, 'cartPriceRange'))
		{
			$cart_price_range_params = [
				'CategoryID'=>$CategoryID,
				'searchSubcategories'=>$searchSubcategories,
				'multipleItemPriceTypes'=>1,
				'round'=>'up'
			];

			// jLog("Cart Price Range Params", $cart_price_range_params );
			$cartPriceRange = $ePIM->cartPriceRange( $cart_price_range_params );
			$minPrice = $cartPriceRange['Price']['PriceMin'] ?: 0;
			$maxPrice = $cartPriceRange['Price']['PriceMax'] ?: 0;
		}
		else
		{
			$ItemIDList = $ePIM->p->epimProductList(array(
				'CategoryID'=>$CategoryID,
				'searchSubcategories'=>$searchSubcategories,
				'column'=>'ItemID'
			));
			if($ItemIDList)
			{
				$ItemIDList = implode(',', $ItemIDList);
				list($minPrice) = $ePIM->epimDB->queryRow("select min(Price) from Pricing where ItemID in ($ItemIDList) and PriceSheetID = 1 and PriceType in ('MIN', 'MAX') and CurrencyCode = 'USD' and PriceUOM = 'PE' and Price > 0 and MaintenanceType <> 'D'");
				list($maxPrice) = $ePIM->epimDB->queryRow("select max(Price) from Pricing where ItemID in ($ItemIDList) and PriceSheetID = 1 and PriceType in ('MIN', 'MAX') and CurrencyCode = 'USD' and PriceUOM = 'PE' and Price > 0 and MaintenanceType <> 'D'");
			}
		}

		if($minPrice > 0 || $maxPrice > 0)
		{
			$eICP->setCurrency(array('currency'=>'USD'));
		}
		$minPriceFormatted = '';
		if($minPrice > 0)
		{
			if ( $numbers_only )
			{
				$minPrice = ceil( $minPrice );
			}
			else
			{
				$minPrice = money_format('%.2n', $minPrice); // changing from ceil to money_format
			}
			$priceRange['min'] = $minPrice;
		}
		$maxPriceFormatted = '';
		if($maxPrice > 0)
		{
			if ( $numbers_only )
			{
				$maxPrice = ceil( $maxPrice );
			}
			else
			{
				$maxPrice = money_format('%.2n', $maxPrice); // changing from ceil to money_format
			}
			$priceRange['max'] = $maxPrice;
		}
		if($minPrice > 0 && $maxPrice > 0)
		{
			if($minPrice == $maxPrice)
			{
				$priceRange['formatted'] = $minPriceFormatted;
			}
			else
			{
				$priceRange['formatted'] = "$minPriceFormatted - $maxPriceFormatted";
			}
		}
		else
		{
			$priceRange['formatted'] = $minPriceFormatted.$maxPriceFormatted;
		}
	}
	return $priceRange;
}



function mod_getMultipleCategoriesPriceRange($params = array())
{

	//Largest possible number in PHP
	$overallMin = PHP_INT_MAX;

	//Zero (since no prices are negative)
	$overallMax = 0;

	$returnArray = array();

	foreach($params['Categories'] as $categoryID)
	{
		$categoryParams = array (
			'CategoryID' => $categoryID,
			'searchSubcategories' => 1,

		);
		$categoryPriceRange = mod_getCategoryPriceRange($categoryParams);

		if($categoryPriceRange['min'] < $overallMin)
		{
			$overallMin = $categoryPriceRange['min'];
		}

		if($categoryPriceRange['max'] > $overallMax)
		{
			$overallMax = $categoryPriceRange['max'];
		}
	}


	$returnArray['min'] = $overallMin;
	$returnArray['max'] = $overallMax;
	return $returnArray;
}

function mod_getStartingAtPrice( $params=array() )
{
	return mod_getCategoryPriceRange( $params );
}

function mod_sortCategoryTiles( $selectedSortOption, $categoryList )
{
	/* Summary: Returns a sorted list of category tiles base on whatever sort option was selected
		Input: `$selectedSortOption` - The sorting option that the customer would like their category tiles sorted by.
				`$categoryList` - The list of category items to sort.
		Returns: A sorted list of category tiles.
	 */
	switch($selectedSortOption)
	{
	case 'popularity':
		usort($categoryList, function($a, $b){
			$popularityA = $a['popularity'] ?: 0;
			$popularityB = $b['popularity'] ?: 0;
			if($popularityA == $popularityB)
			{
				return strcasecmp($a['content_label'], $b['content_label']);
			}
			else
			{
				return $popularityA > $popularityB ? 1 : -1;
			}
		});
		break;
	case 'new':
		usort($categoryList, function($a, $b){
			$newProductA = $a['newProduct'] ?: 0;
			$popularityA = $a['popularity'] ?: 0;
			$newProductB = $b['newProduct'] ?: 0;
			$popularityB = $b['popularity'] ?: 0;
			if($newProductA == $newProductB)
			{
				if($popularityA == $popularityB)
				{
					return strcasecmp($a['content_label'], $b['content_label']);
				}
				else
				{
					return $popularityA > $popularityB ? 1 : -1;
				}
			}
			else
			{
				return $newProductA < $newProductB ? 1 : -1;
			}
		});
		break;
	case 'alpha':
		usort($categoryList, function($a, $b){
			return strcasecmp($a['content_label'], $b['content_label']);
		});
		break;
	case 'reverse_alpha':
		usort($categoryList, function($a, $b){
			return strcasecmp($b['content_label'], $a['content_label']);
		});
		break;
		// Low to High
	case 'price':
		usort($categoryList, function($a, $b){
			$prices_a = mod_getCategoryPriceRange([
				'CategoryID' => $a['id'],
				'searchSubcategories' => 1,
				'numbersOnly' => 1
			]);
			$prices_b = mod_getCategoryPriceRange([
				'CategoryID' => $b['id'],
				'searchSubcategories' => 1,
				'numbersOnly' => 1
			]);

			$priceMinA = $prices_a['min'] ?: 0;
			$priceMaxA = $prices_a['max'] ?: $priceMinA;
			$priceMinB = $prices_b['min'] ?: 0;
			$priceMaxB = $prices_b['max'] ?: $priceMinB;

			if($priceMinA == $priceMinB)
			{
				if($priceMaxA == $priceMaxB)
				{
					return strcasecmp($a['content_label'], $b['content_label']);
				}
				else
				{
					return $priceMaxA > $priceMaxB ? 1 : -1;
				}
			}
			else
			{
				return $priceMinA > $priceMinB ? 1 : -1;
			}
		});
		break;
		// High to Low
	case 'reverse_price':
		usort($categoryList, function($a, $b){
			$prices_a = mod_getCategoryPriceRange([
				'CategoryID' => $a['id'],
				'searchSubcategories' => 1,
				'numbersOnly' => 1
			]);
			$prices_b = mod_getCategoryPriceRange([
				'CategoryID' => $b['id'],
				'searchSubcategories' => 1,
				'numbersOnly' => 1
			]);

			$priceMinA = $prices_a['min'] ?: 0;
			$priceMaxA = $prices_a['max'] ?: $priceMinA;
			$priceMinB = $prices_b['min'] ?: 0;
			$priceMaxB = $prices_b['max'] ?: $priceMinB;

			if($priceMinA == $priceMinB)
			{
				if($priceMinA == $priceMinB)
				{
					return strcasecmp($a['content_label'], $b['content_label']);
				}
				else
				{
					return $priceMinA < $priceMinB ? 1 : -1;
				}
			}
			else
			{
				return $priceMinA < $priceMinB ? 1 : -1;
			}
		});
		break;
	}
	return $categoryList;
}

function mod_eicpGetCartItemCategory( $categoryID, $returnType )
{
	// Since all of the cart items are to be ePIM categories, the category ID must be an ID of an ePIM category
	$categoryContent = pageContentList('', '', '', array(
		'd' => $categoryID,
		'dt' => 80
	));

	// textLog( $categoryContent );

	if ( $returnType === "content" )
	{
		// Return all of that content
		return $categoryContent;
	}
	elseif ( $returnType === "pageContent" )
	{
		// Return all of that pageContent
		return $categoryContent['pageContent'];
	}
	else {
		// Return the single item that you want
		return $categoryContent['pageContent'][$returnType];
	}
}

function mod_extractMultipleItemLeadTime( $order )
{
	$returnString = '';

	$orderItemCategoryIDList = mod_extractCategoryIDListFromOrder( $order );

	$returnString = mod_eicpMultipleItemLeadTimeDispatcher( $orderItemCategoryIDList );

	return $returnString;
}

function mod_extractCategoryIDListFromOrder( $order )
{
	// Returns a categoryID of all of the items in an order
	$orderItemList = $order['itemList'];
	$orderItemCategoryIDList = array();

	// Getting the array list to find the product item lead time
	foreach( $orderItemList as $orderItem )
	{
		if ( ! empty( $orderItem['propertyList'] ) )
		{
			foreach( $orderItem['propertyList'] as $orderItemProperty )
			{
				if
					( (string) $orderItemProperty['order_item_property_key'] === 'CART_CATEGORY_LIST' )
				{
					$orderItemCategoryIDList[] = $orderItemProperty['order_item_property_value'];
				}
			}
		}
	}
	return $orderItemCategoryIDList;
}

function mod_eicpMultipleItemLeadTimeDispatcher( $itemIDArray )
{
	// This function will give us the lead time of the item that has the longest lead time.
	// Yep.

	$leadTimeOverride = 0;

	foreach ( $itemIDArray as $itemID )
	{
		$itemParentCategoryIDArray[] = pageContentList('', '', '', array(
			'contentOnly' => 1,
			'd' => $itemID,
			'dt' => 80
		))['pageContent']['ou_id'];
	}

	// Parent ID's 5 and 6 ( rolling and rack-integrated )
	// have the longest lead time so we have to make sure they
	// will show if they are part of the array.
	if ( in_array(5, $itemParentCategoryIDArray) )
	{
		$eicpParentItemID = 5;
		$leadTimeOverride = 1;
	}
	if ( in_array(6, $itemParentCategoryIDArray) )
	{
		$eicpParentItemID = 6;
		$leadTimeOverride = 1;
	}

	if ( ! $leadTimeOverride )
	{
		$eicpParentItemID = ( int ) array_rand( $itemParentCategoryIDArray );
	}

	return mod_eicpItemLeadTimeDispatcher( $eicpParentItemID, 1, 1 );
}

function mod_eicpItemLeadTimeDispatcher( $itemPageContent, $leadTimeOnly = 0, $parentCategoryID = 0, $order_number = null )
{
	/**
	 * Returns a ePIM_Catgory cart-item "lead time" string.
	 *
	 * Lead times are generated categorically for BAK. This means that two items can have a
	 * 	different lead timem based on the category they are in. Finding the lead time of an
	 * 	item is as simple as finding the item's paren't category, checking if there's a lead time
	 * 	specific to that category, and if not, then we assume the general lead time.
	 *
	 * @param array|int $itemPageContent - if this is an array then you want to pass it the
	 * 	pageContent of an item, and nothing else. If it's an int, you better be passing
	 *  an ePIM_Category id and nothing else.
	 *
	 * @param int $leadTimeOnly - a flag stating on whether we only want to return a product's lead
	 * 	time, or if we want to return the whole product lead time string. Currently the string is
	 * 	only used specifically on cart items and the product selector.
	 *
	 * @param bool $parentCategoryID - a flag stating on whether we are feeding this function a
	 * 	parent's category id already, so we skip the process of looking at the parent's category
	 *  id of the $itemPageContent INT that is being passed in... Look for an example
	 * 	on mod_eicpMultipleItemLeadTimeDispatcher
	 */

	global $myModules;
	$eICP = $myModules->modules['eICP'];

	if ( $order_number )
	{
		$returnString = $eICP->leadTimeDisplay(array(
			'order_id' => $order_number
		));

		return $returnString;
	}
	if ( is_int ( $itemPageContent ) )
	{
		// ePIM_Categories only ( 'dt' => 80 )
		$itemPageContent = pageContentList('', '', '', array(
			'contentOnly' => 1,
			'd' => $itemPageContent,
			'dt' => 80 ) )['pageContent'];
	}

	$returnString = '';
	$itemCategoryID = ( int ) $itemPageContent['id'];
	$itemParentCategoryID = ( int ) $itemPageContent['ou_id'];

	// This is where that $parentCategoryID flag I talked about earlier is used.
	if ( $parentCategoryID )
	{
		$itemParentCategoryID = $itemCategoryID; // We just look at the $itemCategoryID :)
	}

	if ( is_int( $itemCategoryID ) && is_int( $itemParentCategoryID ) )
	{
		/*
			Need items for:
			 * hard folding - 4
			 * rack_integrated - 6
			 * retractable - 7
			 * rolling - 5
		 */
		$shortcode_value_array = array(
			'product_lead_time' => null
		);

		switch ( $itemParentCategoryID ) {
		case 4:
			$itemParentDictionaryKey = 'hard_folding';
			break;
		case 5:
			$itemParentDictionaryKey = 'rolling';
			break;
		case 6:
			$itemParentDictionaryKey = 'rack_integrated';
			break;
		case 7:
			$itemParentDictionaryKey = 'retractable';
			break;
		default:
			$itemParentDictionaryKey = 'default';
			break;
		}

		// textLog( $itemParentDictionaryKey );
		$itemParentDictionaryKey = 'product_' . $itemParentDictionaryKey . '_lead_time';

		$productLeadTime =  mod_getDictionaryTerm(array(
			'dictionary_key' => $itemParentDictionaryKey
		));

		// textLog( $productLeadTime );
		if ( ! $leadTimeOnly )
		{
			$shortcode_value_array['product_lead_time'] = $productLeadTime;

			// textLog( $shortcode_value_array );
			$returnString = mod_getDictionaryTerm(array(
				'dictionary_key' => 'product_information_lead_time_copy',
				'shortcode_value_array' => $shortcode_value_array
			));
		}
		else
		{
			$returnString = $productLeadTime;
		}
	}
	// textLog( $returnString );
	return $returnString;
}

function mod_eicpBuildCartPostProcess( &$cart, $contentReturnType = 'pageContent' )
{
	if(is_array($cart) )//&& array_key_exists('cart_source', $cart ) )
	{
		// textLog( $cart );
		if ( sizeof($cart['itemList']) )
		{
			$itemList = $cart['itemList'];
			foreach( $itemList as $i => $cartItem )
			{
				$customContentArray = array();
				$customContentString = '';

				$categoryName = '';
				foreach( $cartItem['propertyList'] as $cartItemProperty )
				{
					// textLog($cartItemProperty);
					if ( $cartItemProperty['cart_item_property_key'] == 'CART_CATEGORY_LIST' )
					{
						$categoryID = $cartItemProperty['cart_item_property_value'];
						$cartItemCategoryData = mod_eicpGetCartItemCategory( $categoryID, $contentReturnType );

						// $customContentArray[] = array(
						// 	'value' => $cartItemCategoryData['content_label'],
						// 	'class' => "Label"
						// );

						// $customContentArray[] = array(
						// 	'value' => mod_eicpItemLeadTimeDispatcher( $cartItemCategoryData ),
						// 	'class' => 'LeadTime'
						// );
					}
				}
				// Adding the part number to the custom content array
				// $customContentArray[] = array(
				// 	'value' => $cartItem['cart_item_part_number'],
				// 	'class' => "PartNumber",
				// 	'extra_text' => '#',
				// );
				foreach( $customContentArray as $customContentItem )
				{
					$extraText = '';
					if ( array_key_exists('extra_text', $customContentItem) )
					{
						$extraText = $customContentItem['extra_text'] . ' ';
					}

					$customContentString .= <<<HTML
<div class="eicpCartItem{$customContentItem['class']}">$extraText{$customContentItem['value']}</div>
HTML;
				}
				$cart['itemList'][$i]['customContent'] = $customContentString;
			}

		}
	}
	// textLog( $cart );
	return $cart;
}

//trace13
function mod_obtainLocalDealerDealers( $locationArray, $brandID )
{
	if ( $brandID )
	{
		global $myModules;
		$eLocator = $myModules->modules['eLocator'];

		$addressListParams = $locationArray;
		$addressListParams['brand'] = $brandID;


		//$addressListParams['distance'] = 1;
		//$addressListParams['cityStrict'] = 1;
		//$addressListParams['type'] = 3;

		//trace14
		$dealerList = $eLocator->addressList($addressListParams);

		foreach($dealerList as $index => $dealer)
		{

			$typeListParams = array( 'address_id'=>$dealer['address_id'] );


			$dealerList[$index]['typeList'] = $eLocator->typeList($typeListParams);
		}

		// If dealer list is empty, there are probably no dealers available.
		if ( empty( $dealerList ) )
		{
			$dealerList['error'] = 'error_no_dealers_available';
		}

		return $dealerList;
	}
}

function mod_obtainLocalDealerLocations( $locationArray )
{
	global $myModules;
	$eLocator = $myModules->modules['eLocator'];

	$addressListParams = $locationArray;

	$zipList = $eLocator->zipcodeList( $addressListParams );

	return $zipList;
}

function mod_fillInLocationType( $columnName, $locationArray )
{
	/* If we only have a city, what state/county are we in? Ever thought of that there? */
	$dealerList = mod_obtainLocalDealerLocations( $locationArray );

	// If we get returned information with only one state, go ahead and apply that state.
	$locationTypeList = array_column( $dealerList, $columnName );
	// Count up all the occurences of the values
	$locationTypeCountList = array_count_values( $locationTypeList );

	// Figure out the location type based on the count
	$locationType = array_search(  max( $locationTypeCountList ), $locationTypeCountList );
	return $locationType;
}

function mod_createPermutationArray( $array )
{
	// Creates an array of different permutations of input array. For example, an input array of [1, 2] will return [[1,2],[2, 1]]
	$result = [];

	$recurse = function($array, $start_i = 0) use (&$result, &$recurse) {
		if ($start_i === count($array)-1) {
			array_push($result, $array);
		}

		for ($i = $start_i; $i < count($array); $i++) {
			//Swap array value at $i and $start_i
			$t = $array[$i]; $array[$i] = $array[$start_i]; $array[$start_i] = $t;

			//Recurse
			$recurse($array, $start_i + 1);

			//Restore old order
			$t = $array[$i]; $array[$i] = $array[$start_i]; $array[$start_i] = $t;
		}
	};

	$recurse($array);

	return $result;
}

function mod_obtainLocalDealerLocation( $locationArray )
{
	global $myModules;
	$locationArraySize = count( $locationArray );
	$eLocator = $myModules->modules['eLocator'];
	// Should give back an array of params where ['state']['county']['city'] is in it.

	/*
		Takes in a location array finds out which one is state county and city and then gets an eLocator address list based on that input.
	 */

	// Based on each location in the location array, we want to do a count to see which location belongs where.
	$locationTypeArray = array('state', 'county', 'city');
	$locationTypeCountArray = array();
	foreach( $locationArray as $location )
	{
		foreach( $locationTypeArray as $locationType )
		{
			$location = preg_replace('/-/',' ',$location);
			$locationTypeParams = array(
				$locationType => $location,
				'exactLocation' => 1
			);

			$addressList = $eLocator->addressList( $locationTypeParams );


			$locationTypeCountArray[$location][$locationType] = count( $addressList );
		}
	}

	$addressListParams = array();

	// Now go through the results array, count which item has the most items and that is the item in which we will get our location types finally.
	foreach( $locationTypeCountArray as $locationName => $locationTypeCounts )
	{
		if ( ! empty( $addressListParams['state'] ) )
		{
			if ( count( $locationTypeCountArray ) === 2 && count( $locationArray ) === 3 )
			{
				if ( $locationTypeCountArray[$locationName]['county'] && $locationTypeCountArray[$locationName]['city'] )
				{
					$addressListParams['county'] = $locationName;
					$addressListParams['city'] = $locationName;
					break;
				}
			}
		}
		$maxLocationTypeCount = 0;
		$maxLocationTypeName = '';
		foreach( $locationTypeCounts as $locationTypeName => $locationTypeCount )
		{
			if ( $locationTypeCount > $maxLocationTypeCount )
			{
				$maxLocationTypeName = $locationTypeName;
				$maxLocationTypeCount = $locationTypeCount;
			}
		}

		if ( strlen( $maxLocationTypeName ) )
		{
			$addressListParams[$maxLocationTypeName] = $locationName;
		}
	}
	// We should have the same length no matter what homie.
	// This goes through all permutations of the input $locationArray, gets a count of the addressListReturn,
	// and whichever one has a count is the path that we're going to.
	if ( count( $addressListParams ) !== $locationArraySize )
	{
		$locationArrayCopy = $locationArray;

		if ( $locationArraySize === 2 )
		{
			$locationArrayCopy[] = '';
		}

		$permutationArray = mod_createPermutationArray( $locationArrayCopy );
		$permutationCountContainer = array();
		foreach( $permutationArray as $permIndex => $permutation )
		{
			$permutationAddressListParams = array();
			$permutationAddressListParams['state'] = $permutation[0];
			$permutationAddressListParams['county'] = $permutation[1];
			$permutationAddressListParams['city'] = $permutation[2];
			if ( $locationArraySize === 3 )
			{
				$permutationAddressListParams['city'] = $permutation[2];
			}

			$permutationCountContainer[$permIndex] = array(
				'input' => $permutationAddressListParams,
				'count' => 0
			);

			$permutationAddressListParams['exactLocation'] = 1;
			$permutationAddressList = $eLocator->addressList( $permutationAddressListParams );
			$permutationCountContainer[$permIndex]['count'] = count( $permutationAddressList );
		}

		$maxPermutationCount = 0;
		$maxPermutationArray = NULL;

		for ( $i = 0; $i < count( $permutationCountContainer ); $i++ )
		{
			$currentPermCount = $permutationCountContainer[$i]['count'];
			$currentPermArray = $permutationCountContainer[$i]['input'];

			if ( ! $maxPermutationCount || $currentPermCount > $maxPermutationCount )
			{
				$maxPermutationCount = $currentPermCount;
				$maxPermutationArray = $currentPermArray;
			}
		}

		if ( $maxPermutationCount )
		{
			$addressListParams = $maxPermutationArray;
		}
	}

	// Fill in missing values of location types based off of the mising value
	if ( strlen( $addressListParams['city'] ) )
	{
		if ( ! strlen( $addressListParams['state'] ) && ! strlen( $addressListParams['county'] ) )
		{
			$state = mod_fillInLocationType( 'StateName', $addressListParams );

			if ( strlen( $state ) )
			{
				$addressListParams['state'] = $state;
			}
		}
	}

	if ( ! strlen( $addressListParams['county'] ) )
	{
		if ( strlen( $addressListParams['state'] ) && strlen( $addressListParams['city'] ) )
		{
			$county = mod_fillInLocationType( 'CountyName', $addressListParams );

			if ( strlen( $county ) )
			{
				$addressListParams['county'] = $county;
			}
		}
	}
	else
	{
		/* This is used to account for if we just have a county input but no state, we need to
		figure out where the hell state this county is located in! */
		if ( ! strlen( $addressListParams['state'] ) )
		{
			$state = mod_fillInLocationType( 'StateName', $addressListParams );

			if ( strlen( $state ) )
			{
				$addressListParams['state'] = $state;
			}
		}
	}

	if ( $addressListParams['county'] === "None" && ! strlen( $addressListParams['city']  ) )
	{
		unset($addressListParams['county']);
	}

	uksort( $addressListParams, function( $a, $b ) {
		if ( ( $a === "state" && $b === "county" ) || ( $a === "state" && $b === "city" ) )
		{
			return -1;
		}
		if ( $a === "county" && $b === "city" )
		{
			return -1;
		}
		return 1;
	});

	// By this point, we will have an addressListParams array that has values in it of the state, county, or city.
	if ( empty($addressListParams) )
	{
		return 0;
	}
	return $addressListParams;
}


//trace11
function mod_validateLocationAndObtainLocationData( $locationArray, $brandID )
{
	if ( ! empty( $locationArray ) && $brandID)
	{

		$location = mod_obtainLocalDealerLocation( $locationArray );
		//trace12
		$dealerList = mod_obtainLocalDealerDealers( $location, $brandID );
		return array( $location, $dealerList );
	}
}

function mod_encodeLocationURLItem( $string )
{
	return urlencode( implode( '-', explode( ' ', strtolower( $string ) ) ) );
}

function mod_decodeLocationURLItem( $string )
{
	return ucwords( implode( ' ', explode( "-", urldecode( $string ) ) ) );
}

function mod_obtainCategoryIDFromProduct( $product )
{
	global $myModules;
	$ePIM = $myModules->modules['ePIM'];

	// I need the ePIMItemID because the ItemCategoryXREF table is a cross reference between the item ID and the category ID.
	$ePIMItemID = $product['ItemID'];

	$categoryList = $ePIM->categoryList(array('ItemID' => $ePIMItemID ));

	// $ItemCategoryXref = $ePIM->epimDB->queryRow("SELECT CategoryID FROM ItemCategoryXREF WHERE ItemID = ?" . $ePIMItemID);

	$ItemCategoryID = $categoryList[0]['CategoryID'];

	return $ItemCategoryID;
}
/**
 * Returns an array filled with the tableNames of the top level items within the
 * combined content array.
 *
 * NOTICE: This works best with combined content
 * @param  array $contentList A list of content generated by eCMP.
 * @return array $tableNameArray An array filled with the table names of the top level content items.
 */
function mod_obtainContentTableNameArray( $contentList )
{
	$tableNameArray = array();

	foreach( $contentList as $content_item_index => $content_item )
	{
		$itemPageContent = NULL;
		// Some items don't have pageContent for some reason, so IDK.
		if ( empty( $content_item['pageContent'] ) )
		{
			$itemPageContent = $content_item;
		} else {
			$itemPageContent = $content_item['pageContent'];
		}

		$itemTableName = $itemPageContent['tableName'];

		$tableNameArray[] = $itemTableName;
	}
	return $tableNameArray;
}

/**
 * Sometimes content items return with pageContent, subContent and subOus
 * Sometimes they dont.
 * A lot of the times you want to get that pageContent.
 * @param  array $contentItem A content item, for example, a page element or even a page!
 * @return array              The `pageContent` of that item.
 */
function mod_obtainContentItemPageContentArray( $contentItem )
{
	$contentItemPageContent = NULL;

	if ( empty( $contentItem['pageContent'] ) )
	{
		$contentItemPageContent = $contentItem;
	} else {
		$contentItemPageContent = $contentItem['pageContent'];
	}
	return $contentItemPageContent;
}

function mod_sortProductAssetList( $AssetList )
{
	usort( $AssetList, function( $asset1, $asset2 ) {
		$sequence1 = ( int ) $asset1['Sequence'];
		$sequence2 = ( int ) $asset2['Sequence'];

		if ( !$sequence1 && !$sequence2 || $sequence1 === $sequence2 )
		{
			return 0;
		}
		else
		{
			return ( $sequence1 < $sequence2 ) ? -1 : 1;
		}
	});

	return $AssetList;
}

/**
 * I made this as a controller type function that obtains the categories that are (in the developers terms)
 * related to each other based on the input $itemIDArray
 *
 * @param  Array $ItemIDArray Will work ONLY if we give itemIDs
 * @param String $contentReturnType Lets the controller know where to take this data.
 * @param  String $contentReturnFormat Tells us how to return the data
 * @param String $contentReturnFormatFlag Based on the content return type, gives different results. Used as an extra data flag.
 * @return              [description]
 */
function mod_obtainRelatedItemCategories( $ItemIDArray, $contentReturnType, $contentReturnFormat = 'html', $contentReturnFormatFlag = 'li' )
{
	global $myModules;
	$ePIM = $myModules->modules['ePIM'];


	$returnData = array();

	///////////////////////////////////////////////////////////////
	// Get unique values in the possibly not unique $ItemIDArray //
	///////////////////////////////////////////////////////////////
	$ItemIDArray = array_values( array_unique( $ItemIDArray ) );
	$sql = '';

	/**
	 * This content return type should return an array with key => CategoryID and value = ComponentItemId
	 * @var [type]
	 */
	if ( $contentReturnType == 'replacement-items')
	{
		$QueryArrayString =  '(' . implode(', ', $ItemIDArray) . ')';

		$sql = <<<SQL
SELECT DISTINCT ItemCategoryXREF.CategoryID, Kit.ItemID, Kit.ComponentItemID FROM Kit INNER JOIN ItemCategoryXREF ON Kit.ItemID = ItemCategoryXREF.ItemID WHERE ComponentItemID IN $QueryArrayString ORDER BY ItemCategoryXREF.CategoryID
SQL;
		$replacee_item_list = $ePIM->epimDB->queryAllAssoc($sql);

		foreach ( $replacee_item_list as $replacee_item )
		{
			$replacee_category_id = $replacee_item['CategoryID'];
			$replacement_item_id = $replacee_item['ComponentItemID'];

			if ( empty( $returnData[$replacee_category_id] ) )
			{
				$returnData[$replacee_category_id] = array();
			}

			$returnData[$replacee_category_id][] = $replacement_item_id;
		}

		// Now clean up that data
		foreach ( $returnData as $CategoryID => $ItemIDList )
		{
			$returnData[$CategoryID] = array_values( array_unique( $ItemIDList ) );
		}
	}
	return $returnData;
}


/**
 * Obtains all applicable review information for a category, including the standardized
 * HTML for easy insert into any template
 * @param  mixed $categoryID Category ID that you want to get reviews for
 * @return array             Array filled with category review information
 */
function mod_getCategoryReviewInformation( $categoryID )
{
	global $core;
	global $myModules;

	$eCCM = $myModules->modules['eCCM'];

	$review_params = [];
	$review_params['total'] = 1;
	$review_params['include_column'][] = 'review_total_avg_rating';
	$review_params['CategoryID'] = $categoryID;
	$review_params['review_active'] = 1;
	$review_data = $eCCM->reviewList( $review_params );

	// jLog("mod review params0");
	// jLog($review_params);
	// Rating obtained for ePIM_Category
	$review_rating = $review_data['review_total_avg_rating']['review_total_avg_rating'];

	// Scale of rating obtained for ePIM_Category
	$review_scale = $review_data['review_total_avg_rating']['review_total_avg_rating_scale'];

	// Total amount of reviews given for ePIM_Category
	$review_total_amount = $review_data['total'] ?: "";

	// returnArray will have reviews come back as an array of keys/values
	return [
		'rating' => $review_rating,
		'scale' => $review_scale,
		'total' => $review_total_amount,
	];
}

// Remove all promotions in which their purchase expiration date is great than today's date
function mod_filterPromotionsByPurchaseExpirationDate( $promotions ) {
	$todays_date = date('Y-m-d');
	return $promotions = array_filter($promotions, function( $promotion ) use ( $todays_date )
	{
		if ( ! isset( $promotion['coupon_campaign_properties']['purchase_expiration_date'] ) )
		{
			return 1;
		}

		return $todays_date <= $promotion['coupon_campaign_properties']['purchase_expiration_date'];
	});
}

// Remove all promotions in which their campaign end date is greater than today's date
function mod_filterPromotionsByEndDate ( $promotions ) {
	$todays_date = date('Y-m-d H:i:s');
	return $promotions = array_filter($promotions, function( $promotion ) use ( $todays_date )
	{
		return $promotion['coupon_campaign_end_date'] > $todays_date;
	});
}

/**
 * Obtains promotion information by specifically using
 * $eICP->admin->couponCampaignList() with the input category ID.
 * @param  mixed $categoryID categoryID or list oof an ePIM_Category
 * @param int $limit Limits the amount of coupon campaigns that are returned with the couponCampaignList call
 * @return array             return from eICP->admin->couponCampaignList
 */
function mod_getPromotionInformation( $categoryID = null , $limit = 0, $filter_fn = NULL )
{
	global $myModules;
	global $siteInformation;

	$params = [];
	$eICP = $myModules->modules['eICP'];

	$category_id_array = [];

	if ( is_array( $categoryID ) )
	{
		$category_id_array = array_merge( $category_id_array, $categoryID );
	}
	else
	{
		$category_id_array[] = $categoryID;
	}

	if ( $categoryID )
	{
		$params['ePIM_category_id'] = $category_id_array;
	}

	$params['coupon_campaign_type_key'] = [
		'FACTORY_REBATE',
		'GIFT_REDEMPTION',
		'INSTANT_FREE_GIFT'
	];

	// In order to test promotions out before they go live,
	// we will make the "test" codebase not care about web_active status

	if ( $_SESSION['codeBase'] == 'TEST' )
	{
		$params['coupon_campaign_web_active'] = 0;
	}
	else
	{
		$params['coupon_campaign_web_active'] = 1;
	}

	$params['category_content'] = 1;
	$params['coupon_campaign_category_list'] = 1;
	$params['coupon_campaign_category_list_strict'] = 1;


	if ( $limit )
	{
		$params['limit'] = $limit;
	}

	$couponCampaignList = $eICP->admin->couponCampaignList( $params );

	 //jLog("Before Filter", $couponCampaignList);
	switch ( $filter_fn ) {
	case 'purchase_expiration_date_filter':
		$couponCampaignList = mod_filterPromotionsByPurchaseExpirationDate( $couponCampaignList );
		break;
	case 'end_date_filter':
		$couponCampaignList = mod_filterPromotionsByEndDate( $couponCampaignList );
		break;
	default:
		$couponCampaignList = $couponCampaignList;
	}

	//jLog("After Filter",  $couponCampaignList );
	return $couponCampaignList;
}

/**
 * Specifically extracts basic content from ePIM_Categories as an array
 * @param  mixed $pageContent $pageContent of an ePIM_Category OR A CATEGORY ID
 * @return array              Specific information from ePIM Categories.
 */
function mod_extractEpimCategoryContent( $pageContent )
{
	global $core;
	global $myModules;

	$eICP = $myModules->modules['eICP'];

	if ( ! is_array($pageContent) )
	{
		$pageContent = pageContentList('', '', '', [
			'd' => $pageContent,
			'dt' => 80,
			'OULimit' => 1,
			'contentOnly' => 1
		])['pageContent'];
	}

	$returnArray = array();

	///////////////////////////////////////////////
	// Obtaining the promotion for this category //
	///////////////////////////////////////////////

	$couponCampaignList = mod_getPromotionInformation( $pageContent['id'], null, 'purchase_expiration_date_filter' );

	$returnArray['promotion'] = $couponCampaignList;

	/////////////////////////////////////
	// Is this category a new product? //
	/////////////////////////////////////
	if ( $pageContent['newProduct'] )
	{
		$returnArray['new'] = 1;
	}

	////////////////////
	// Build the Link //
	////////////////////
	$find_a_dealer_data_id = 1;
	$find_a_dealer_data_table = 123;
	$returnArray['links'] = [
		'category' => mod_buildURL($pageContent),
		'local-dealers' => mod_buildURL(
			pageContentList('', '', '', [
				'd' => $find_a_dealer_data_id,
				'dt' => $find_a_dealer_data_table
			])['pageContent']
		)
	];

	$returnArray['is_comparable'] = $pageContent['is_comparable'];
	$returnArray['description'] = [
		'long' => trim(
			mb_strimwidth(
				$pageContent['quickViewDescription'],
				0,
				300,
				'...'
			)
		),
		'short' => trim(
			mb_strimwidth(
				$pageContent['quickViewDescription'],
				0,
				120,
				'...'
			)
		),
		'full' => trim(
			$pageContent['quickViewDescription']
		)
	];

	/////////////////////////////
	// Get Pricing Information //
	/////////////////////////////
	$price_range_params = [
		'CategoryID' => (int) $pageContent['data_id'],
		'searchSubcategories' => 1
	];

	$returnArray['price'] = mod_getCategoryPriceRange( $price_range_params );

	///////////////////////////////////////
	// GET GENERAL CATEGORY INFORMATION  //
	///////////////////////////////////////
	$returnArray['id'] = $pageContent['id'];
	$returnArray['image'] = $pageContent['thumbnail'];
	$returnArray['subtitle'] = $pageContent['subtitle'];
	$returnArray['title'] = $pageContent['content_label'];
	$returnArray['title_2'] = $pageContent['label'];

	/////////////////////////////
	// GET REVIEW INFORMATION  //
	/////////////////////////////
	$returnArray['reviews'] = mod_getCategoryReviewInformation( $pageContent['id'] );

	return $returnArray;
}

/**
 * Put in an ePIM_Category_id, get back the content of that item.
 *
 * @param  [type] $category_link [description]
 * @param array $exclude_additions - An array to add on 'strictTypeArray' values
 * @return array - An eCMP category item.
 */
function mod_obtainFilteredCategoryInfo($category_link, $exclude_additions=['elementCategoryProductFeature'] ) {
	return pageContentList('', '', '', [
		'd' => $category_link,
		'dt' => 80,
		'OULimit' => 1,
		'strictTypeArray' => array_merge(
			['ePIM_Category'],
			$exclude_additions
		)
	]);
}
/**
 * Works in tandem with mod_obtainElementCategoryProductFeatures by being
 * the item extraction process.
 */
function mod_extractAndBuildElementCategoryProductFeatures( $entry_point_subcontent )
{
	$Template = new Template;

	if ( empty( $entry_point_subcontent ) )
	{
		return 0;
	}

	$returnContentArray = [];

	foreach ( $entry_point_subcontent as $item )
	{
		$returnContentArray[] = trim(array_shift(
			$Template->getTemplateByChildType([$item])
		));
	}

	if ( !empty( $returnContentArray ) )
	{
		$returnContentHTML = implode('', $returnContentArray);
	}

	$returnString = <<<HTML
	<div class="element-category-product-features-wrapper">
		$returnContentHTML
	</div>
HTML;
	return $returnString;
}

/**
 * This function recursively goes through the content tree from "bottom" to "top", you might say.
 * If we ever have a category in which its parent is not an ePIM category, then we know we're at the top my man!
 */
function mod_obtainElementCategoryProductFeatures( $entry_point_content, $callback=mod_obtainFilteredCategoryInfo )
{
	// return case 1
	// If we have a good case of elementCategoryProductFeatures, then
	// we can try and build those through our methods here.
	if ( ! empty( $entry_point_content['subContent'] ) )
	{
		return mod_extractAndBuildElementCategoryProductFeatures(
			$entry_point_content['subContent']
		);
	}
	// Return case 2
	// If this category's parent is not an epim category itsef
	if ( $entry_point_content['pageContent']['ou_table'] != 80 )
	{
		return;
	}

	// Getting the category info for the parent, you see?
	$filtered_parent_category_info = $callback( $entry_point_content['pageContent']['ou_id'], ['elementCategoryProductFeature'] );

	// recursion
	$returnArray = mod_obtainElementCategoryProductFeatures(
		$filtered_parent_category_info,
		$callback
	);
	return $returnArray;
}


/**
 * Builds a URL for a page from the content tree by combining the steps that are required to actually get this information
 * @param  int $d  data_id of type
 * @param  int $dt data_table of type
 * @return string     URL string of the input $d and $dt
 */
function mod_buildCategoryURL( $d, $dt )
{
	// Return just a basic homepage url if input isn't good.
	if ( ! strlen( $d ) || ! strlen( $dt ) )
	{
		return mod_buildURL();
	}

	$pageContent = pageContentList('', '', '', [
		'd' => $d,
		'dt' => $dt,
		'contentOnly' => 1,
		'OULimit' => 1,
	]);

	// First get the pageContent
	$returnArray = $pageContent['pageContent'];

	// Some types don't actually have pageContent, that's why we're returning the whole pageContentList return if there is
	// no ['pageContent']
	if ( ! count( $returnArray ) )
	{
		$returnArray = $pageContent;
	}

	return mod_buildURL( $returnArray );
}

function mod_obtainResponsiveImageStrings( $base_img, $sizes_array, $general_height, $general_quality, $general_crop )
{
	global $core;

	$img_srcset_string = '';
	$img_sizes_string = '';
	$responsive_image_count = 1;
	foreach ( $sizes_array as $img_var_name => $img_data_array )
	{
		$image_parameters = [
			'image' => $base_img,
			'width' => $img_data_array['size_width'],
			'strip' => 1,
			'samplingFactor' => '4:2:0',
			'quality' => 75,
		];


		/////////////////////
		// Height Handling //
		/////////////////////

		if ( $general_height )
		{
			$image_parameters['height'] = $general_height;
		}

		if ( $img_data_array['size_height'] )
		{
			$image_parameters['height'] = $img_data_array['size_height'];
		}


		///////////////////////
		// Quality Handling  //
		///////////////////////

		if ( $general_quality )
		{
			$image_parameters['quality'] = $general_quality;
		}

		if ( $img_data_array['quality'] )
		{
			$image_parameters['quality'] = $img_data_array['quality'];
		}


		///////////////////
		// Crop Handling //
		///////////////////

		if ( $general_crop )
		{
			$image_parameters['crop'] = $general_crop;
		}

		if ( $img_data_array['crop'] )
		{
			$image_parameters['crop'] = $img_data_array['crop'];
		}

		//////////////////////////////////////////
		// Building Image through getImage here //
		//////////////////////////////////////////
		$$img_var_name = $core->getImage( $image_parameters );


		////////////////////////////////////////////
		// Building Responsive Image String Here  //
		////////////////////////////////////////////
		$responsive_image_delimiter = ', ';

		if ( $responsive_image_count === count( $sizes_array ) )
		{
			$responsive_image_delimiter = '';
		}

		$img_srcset_string .= $$img_var_name . ' ' . $img_data_array['size_width'] . 'w' . $responsive_image_delimiter;

		// Resetting sizes_media_query
		$sizes_media_query = '';

		if ( $img_data_array['screen_size'] )
		{
			$sizes_media_query = '(max-width: ' . $img_data_array['screen_size'] . 'px) ';
		}
		$img_sizes_string .= $sizes_media_query . $img_data_array['size_width'] . 'px' . $responsive_image_delimiter;

		// Up the count
		$responsive_image_count++;
	}

	return [ $img_srcset_string, $img_sizes_string ];
}

/**
 * Gives a timestamp to a file in order to improve the cacheing ability of our site
 */
function mod_addAssetCacheString( $path )
{
	global $siteInformation;

	if ( substr( $path, 0, 6 ) == '/site/' )
	{
		$cacheValue = filemtime( str_replace( '//', '/', $siteInformation['siteBaseDir'] . $path ) );

		if ( strlen( $cacheValue ) )
		{
			$path .= '?cache=' . $cacheValue;
		}
	}

	return $path;
}

/////////////////////////////////////////
// PRODUCT SELECTOR SPECIFIC FUNCTIONS //
/////////////////////////////////////////

function mod_buildSelectbox( $param_array )
{
	global $myModules;
	$Content = $myModules->modules['Content'];

	return $Content->selectbox( $param_array );
}
////////////////////////////////////
// SESSION MANIPULATION FUNCTIONS //
////////////////////////////////////
function mod_insertIntoProductSelectorSession( $type, $key, $value )
{
	$_SESSION['product_selector']['selected_item_array'][$type][$key] = $value;
}
function mod_getProductSelectorSession() {
	return $_SESSION['product_selector']['selected_item_array'];
}
function mod_clearProductSelectorSession()
{
	$_SESSION['product_selector']['selected_item_array'] = [];
}

/**
 * Surgically clears out the product selector session
 * @param  string $type      Clears all items starting at the $type and beyond
 * @param  string $inner_key Clears all items in the $type starting at the $inner_key level and beyond
 * @return [type]            [description]
 */
function mod_snipProductSelectorSession( $type, $inner_key, $inner_value )
{
	$session_copy = mod_getProductSelectorSession();
	$session_inner_copy = mod_getProductSelectorSession()[$type];

	$session_key_index = 0;

	$temp_array = [];

	foreach( $session_copy as $session_key => $session_value )
	{
		if ( ( string ) $type === ( string ) $session_key )
		{
			break;
		}
		$temp_array[$session_key] = $session_value;
	}

	$session_copy = $temp_array;

	$inner_temp_array = [];

	foreach ( $session_inner_copy as $session_inner_key => $session_inner_value )
	{
		if ( $session_inner_key == $inner_key )
		{
			break;
		}

		$inner_temp_array[$session_inner_key] = $session_inner_value;
	}

	$session_inner_copy = $inner_temp_array;

	$session_copy[$type] = $session_inner_copy;

	mod_clearProductSelectorSession();

	$_SESSION['product_selector']['selected_item_array'] = $session_copy;
}

function mod_getProductSelectorFitmentSession() {
	return mod_getProductSelectorSession()['fitment'];
}
function mod_getProductSelectorFitmentQualifierSession() {
	return mod_getProductSelectorSession()['fitment_attribute_qualifier'];
}
function mod_getProductSelectorProductQualifierSession() {
	return mod_getProductSelectorSession()['product_attribute_qualifier'];
}
function mod_getProductSelectorProductSession() {
	return mod_getProductSelectorSession()['product_selection'];
}

function mod_clearFitmentSession() {

	$fitment_array = $_SESSION['fitment'];

	foreach( $fitment_array as $fitment_session_item )
	{
		$fitment_Session_item = "";
	}

	$_SESSION['fitment'] = $fitment_array;
}
function mod_selectionInProductSelectorSession( $type, $key )
{
	return isset( mod_getProductSelectorSession()[$type][$key] );
}
function mod_getFitmentSession() {
	return $_SESSION['fitment'];
}
function mod_selectionInFitmentSession( $key, $value )
{
	return mod_getFitmentSession()[$key] === $value;
}
function mod_extractVehicleFitmentData( $item_array )
{
	$returnArray = [];

	$vehicle_fitment_check_array = array_column(
		mod_fitmentSelectorList(),
		'searchField'
	);

	foreach( $vehicle_fitment_check_array as $vehicle_fitment )
	{
		if ( isset( $item_array[$vehicle_fitment] ) )
		{
			$returnArray[$vehicle_fitment] = $item_array[$vehicle_fitment];
		}
	}

	return $returnArray;
}

function mod_getFitmentVehicleSession() {
	return mod_extractVehicleFitmentData( mod_getFitmentSession() );
}
function mod_getProductSelectorVehicleSession() {
	return mod_extractVehicleFitmentData( mod_getProductSelectorFitmentSession() );
}

/**
 * Checks to see if we're in a current state of "vehicle selection"
 * This means that Year, Make, and Model are selected
 * @param  array $item_array array of items that contain fitment-oriented values which
 *                           can be used to check against the session
 * @return int             0 or 1
 */
function mod_inVehicleSelectionState( $item_array )
{
	$vehicle_selection_check_array = [
		'YearID',
		'MakeID',
		'ModelID'
	];

	foreach ( $vehicle_selection_check_array as $selection_check )
	{
		if ( ! isset( $item_array[$selection_check] ) )
		{
			return 0;
		}
	}

	return 1;
}
/* End Product Selector Specific Functions */

// Returns the category content of an ePIM_Category. NOT STABLE.
function mod_recursiveBottomUpContentSearch( $start_id, $start_dt, $search_dt = 80, $OULimit = 1 ) {
	$content = pageContentList('', '', '', [
		'd' => $start_id,
		'dt' => $start_dt,
		'OULimit' => $OULimit
	]);

	if ( $content['pageContent']['data_table'] == $search_dt )
	{
		return $content;
	}

	if ( $content['pageContent']['data_table'] == 1 )
	{
		return 0;
	}

	// return;

	return mod_recursiveBottomUpContentSearch(
		$content['pageContent']['ou_id'],
		$content['pageContent']['ou_table'],
		$search_dt
	);
}

/**
 * Searches through all elements of content through a given input array and returns the FIRST INSTANCE of that
 * content.
 * @param  array  $input_array Content array that will be searched through.
 * @param  integer $search_dt   data_table id of the content item that we want returned.
 * @return mixed               NULL or Array of pageContent
 */
function mod_topDownContentSearch( $input_array, $search_dt = 80 ) {
	$cross_reference_page_content_array = null;
	$returnValue = null;

	// Case 1: Empty Input Array
	if ( empty( $input_array ) )
	{
		return null;
	}

	// Normalizing the cross_reference_page_content_array
	if ( isset( $input_array['pageContent'] ) )
	{
		$cross_reference_page_content_array = $input_array['pageContent'];
	}
	else
	{
		$cross_reference_page_content_array = $input_array;
	}

	// Case 2: We have a value
	if ( $cross_reference_page_content_array['data_table'] == $search_dt )
	{
		return pageContentList('', '', '', [
			'd' => $cross_reference_page_content_array['id'],
			'dt' => $cross_reference_page_content_array['data_table'],
			'OULimit' => 1
		]);
	}

	$combined_content = mod_combineContent( $input_array );

	foreach( $combined_content as $content )
	{
		$search_item_value = mod_topDownContentSearch( $content, $search_dt );

		if ( ! empty( $search_item_value ) )
		{
			$returnValue = $search_item_value;
			break;
		}
	}

	return $returnValue;
}


/**
 * Combines the subContent and subOus of an item and performs a 'pageContentList' sort on the items.
 * @param  array $content pageContent of an item from the content tree
 * @return array
 */
function mod_combineContent( $content )
{
	if ( empty( $content ) )
	{
		return $content;
	}

	$combined_content = null;

	if ( ! isset( $content['combinedContent'] ) )
	{
		$combined_content = array_merge( $content['subOus'], $content['subContent'] );
		usort($combined_content,'pageContentListSort');
	}

	return $combined_content;
}

/**
 * Returns the vehicle-selected url of the current
 * @param  [type] $d  [description]
 * @param  [type] $dt [description]
 * @return [type]     [description]
 */
function mod_buildContentURL( $d, $dt ) {
	return mod_buildURL( pageContentList( '', '', '', [
		'd' => $d,
		'dt' => $dt,
		'OULimit' => 1,
		'contentOnly' => 1
	] )['pageContent'] );
}

function mod_getResponseValues($params)
{
	global $myModules;
	$eSBM = $myModules->modules['eSBM'];
	$response_id = getParams('response_id',$params);

	if($response_id)
	{
		$responseItemList = $eSBM->responseItemList(array('response_id'=>$response_id, 'report' => 1));
		$responseItemValues = array_column($responseItemList,'response_item_value','answer_process_key');
		header('Content-Type: application/json');
		echo  json_encode($responseItemValues);
	}

}

function mod_isUniversalFitment( $epim_category_id ) {
	global $myModules;

	$ePIM = $myModules->modules['ePIM'];

	$fitment_check_list = $ePIM->p->fitmentList([
		'WebActive' => 1,
		'CategoryID' => $epim_category_id,
		'column' => 'YearID',
		'limit' => 1
	]);

	return empty( $fitment_check_list );
}

function mod_getPartIDFromFitment($params)
{
	global $myModules;

	$ePIM = $myModules->modules['ePIM'];

	$fitmentProductList = $ePIM->p->epimProductList( $params );

	$fitmentProductList = array_map(function($value){
		return $value['PartNumber'];
	},$fitmentProductList);
	header('Content-Type: application/json');
	echo json_encode($fitmentProductList);
}







///////////////////////////////////////////////////////////////////////////////

function mod_getDealerFormatted($dealer, $preferredTypeID)
{
	global $myModules;
	$Member = $myModules->modules['Member'];

	$dealer['company'] = trim($dealer['company']);
	$dealer['address'] = trim($dealer['address']);
	$dealer['address2'] = trim($dealer['address2']);
	$dealer['city'] = trim($dealer['city']);
	$dealer['state'] = trim($dealer['state']);
	$dealer['zipcode'] = str_replace(' ','&nbsp;',trim($dealer['zipcode']));
	$dealer['country'] = trim($dealer['country']);
	$dealer['zipcode'] = trim($dealer['zipcode']);
	$dealer['phone'] = trim($dealer['phone']);
	$dealer['url'] = trim($dealer['url']);

	// Formatting address
	$addressFormatted = [];

	if($dealer['address'])
	{
		$completeAddress[] = $dealer['address'];
	}

	if($dealer['address2'])
	{
		$completeAddress[] = $dealer['address2'];
	}


	if($dealer['city'] && $dealer['state'])
	{
		$addressFormatted[] = trim("{$dealer['city']}, {$dealer['state']} {$dealer['zipcode']}");
	}
	else
	{
		if($city)
		{
			$addressFormatted[] = $city; }
		if($state){ $addressFormatted[] = $state; }
		if($zipcode){ $addressFormatted[] = $zipcode; }
	}

	$dealer['addressFormatted'] = implode('<br />',$addressFormatted);
	$dealer['preferred'] = false;

	//Checking if this is a preferred dealer or not.
	if(sizeof($dealer['typeList']) && $preferredTypeID)
	{
		foreach($dealer['typeList'] as $dealer_type)
		{
			if($dealer_type['type_id'] == $preferredTypeID)
			{
				$dealer['preferred'] = true;
			}
		}
	}

	if($dealer['phone'])
	{
		$phoneParams = array('phone'=>$dealer['phone'],'country'=>$country);
		$dealer['phoneFormatted'] = $Member->memberFormatPhone($phoneParams);
	}

	if(!$dealer['url'] || !filter_var($dealer['url'], FILTER_VALIDATE_URL))
	{
		$dealer['url'] = null;
	}

	$directionsURLAddress = implode(" ",$addressFormatted);
	$directionsURLParams = [];
	$directionsURLParams['saddr'] = 'My Location';
	$directionsURLParams['daddr'] = $directionsURLAddress;
	$directionsURLParams['directionsmode'] = 'driving';
	$directionsURL = 'https://maps.google.com/maps?'. http_build_query($directionsURLParams);
	$dealer['directions_url'] = $directionsURL;

	$parts = [
		'local-dealers',
		'location',
		$dealer['state'],
		$dealer['county'],
		$dealer['city'],
		'dealer',
		$dealer['eMRM_company_id'],
		$dealer['company']
	];

	$parts = array_map(function($value){
		if($value && $value != 'none')
		{
			return mod_encodeLocationURLItem($value);
		}

	},$parts);

	$dealerUrl = '/' . implode('/',array_filter($parts));
	$dealer['moreInfoUrl'] = strtolower($dealerUrl);
	return $dealer;
};

/**
* Returns an alias model name based off of the content tree.
*
* Goes through a list of epim_vehicle_model_alias content tree types stored in System > Vehicle Model Aliases
* and returns the alias or the original model name.
*
* @param string $name The original model name
* @param int $year The model year.
* @param int $model_id The model id.
*
* @return string
*/
function mod_ModelAliasName($name,$year_id,$model_id)
{
	global $core;

	$aliases = $core->templateVars['modelAliases'];
	if(!is_array($aliases))
	{
		$aliases = $core->templateVars['modelAliases'] = pageContentList('','','',array('d'=>10,'dt'=>81))['subContent'];
	}

	foreach($aliases as $alias)
	{
		if($year_id == $alias['year'] && $model_id == $alias['modelID'])
		{
			return $alias['alias'];
		}
	}
	return $name;
}

?>