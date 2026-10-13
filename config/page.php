<?php
	class Page{    
		public static function add($pageName, $pageTitle = NULL, $css = false, $js = false, $pageDesc = NULL, $noLayout = false)    
		{        
			global $smarty;

			$pageTitle = $pageTitle ?? '';
			$pageDesc = $pageDesc ?? '';
			$documentTitle = class_exists('Seo', false)
				? Seo::formatDocumentTitle((string) $pageTitle, (string) $pageName)
				: (string) $pageTitle;

			$seoMeta = Seo::technicalMeta((string) $pageName, [
				'productUrl' => $smarty->getTemplateVars('productUrl'),
				'catalogBaseUrl' => $smarty->getTemplateVars('catalogBaseUrl'),
				'catalogFilter' => $smarty->getTemplateVars('catalogFilter'),
				'pagination' => $smarty->getTemplateVars('pagination'),
				'imageUrl' => $smarty->getTemplateVars('imageUrl'),
			], $_GET);
			if ($seoMeta['robots'] !== 'index, follow' && !headers_sent()) {
				header('X-Robots-Tag: ' . $seoMeta['robots']);
			}
			$smarty->assign('seoMeta', $seoMeta);


			$smarty->assign([            
				'pageName'  => $pageName,            
				'css'       => $css,            
				'js'        => $js,            
				'pageTitle' => $pageTitle,
				'documentTitle' => $documentTitle,
				'pageDesc' 	=> $pageDesc,        
			]);

			$pageSchemas = $smarty->getTemplateVars('schemaJsonLd');
			$pageSchemas = is_array($pageSchemas) ? $pageSchemas : [];
			$smarty->assign('schemaJsonLd', array_merge(
				SchemaOrg::getGlobalScripts((string) $documentTitle, (string) $pageDesc, $seoMeta['canonical']),
				$pageSchemas
			));

			if (class_exists('Performance', false)) {
				Performance::assignThemeStylesheets($smarty);
			}

			$prefix = '';
			if ($noLayout)
				$prefix = '-login';
			$smarty->display(_THEME_BASE_DIR_ . 'header'.$prefix.'.tpl');       
			$smarty->display(_THEME_BASE_DIR_ . $pageName . '.tpl');        
			$smarty->display(_THEME_BASE_DIR_ . 'footer'.$prefix.'.tpl');
		}
	}
