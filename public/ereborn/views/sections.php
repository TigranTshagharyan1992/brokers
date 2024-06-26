<?php
	if( !defined("APP") )
	{
		exit("ERROR");
	}
?>
<?php
	$pid = (string)filter_input(INPUT_GET, "pid", FILTER_VALIDATE_INT);
	if($pid!=="")
	{
		$parentEntity = false;
		
		$templateId = false;
		
		if($pid!=="0")
		{
			$parentEntity = getEntity($pid);
			
			if($parentEntity!==false)
			{
				$templateId = $parentEntity["entity_type"];
			}
			
			if($parentEntity!==false && USER_TYPE==="AUTHOR" && $parentEntity["entity_role"])
			{
				if( !in_array($parentEntity["entity_role"], getUserRoles($_SESSION["user"])) )
				{
					exit("<meta http-equiv='refresh' content='0; url=".$_SERVER["SCRIPT_NAME"]."'>");
				}
			}
		}
		
		if($pid==="0" || $parentEntity!==false)
		{
			$validQueryParams = array("sections"=>"", "pid"=>$pid);
			
			if( isset($_POST["orders"]) && is_array($_POST["orders"]) )
			{
				$_POST["orders"] = array_reverse($_POST["orders"]);
				
				foreach($_POST["orders"] as $key=>$val)
				{
					$key = (int)$key;
					
					$val = (int)$val;
					
					$db->request("UPDATE entities SET entity_order=? WHERE entity_id=?", array($key, $val));
				}
				
				exit("<meta http-equiv='refresh' content='0; url=".$_SERVER["SCRIPT_NAME"]."?".http_build_query($validQueryParams)."'>");
			}
			else
			{
				$where = "";
				
				$data = array($pid);
				
				if(USER_TYPE==="AUTHOR")
				{
					$userRoles = getUserRoles($_SESSION["user"]);
					if( count($userRoles) > 0 )
					{
						$where .= " AND (entity_role IN (".implode(",", $userRoles).") OR entity_role IS NULL)";
					}
					else
					{
						$where .= " AND entity_role IS NULL";
					}
				}
				
				if( isset($_GET["keyword"]) && is_string($_GET["keyword"]) && trim($_GET["keyword"])!=="" )
				{
					$where .= " AND ".TITLE_FIELD." LIKE ?";
					
					$data[] = "%".$_GET["keyword"]."%";
					
					$validQueryParams["keyword"] = $_GET["keyword"];
				}
				
				if( isset($_GET["reducer"]) && is_string($_GET["reducer"]) )
				{
					$reducerName = $_GET["reducer"];
					
					if( isset($reducers) && isset($reducers[$reducerName]) )
					{
						$where .= " ".$reducers[$reducerName]();
						
						$validQueryParams["reducer"] = $reducerName;
						
						if( isset($_GET["frameEntity"]) && is_string($_GET["frameEntity"]) && ctype_digit($_GET["frameEntity"]) )
						{
							$validQueryParams["frameEntity"] = $_GET["frameEntity"];
						}
						
						if( isset($_GET["frameParent"]) && is_string($_GET["frameParent"]) && ctype_digit($_GET["frameParent"]) )
						{
							$validQueryParams["frameParent"] = $_GET["frameParent"];
						}
					}
				}
				
				$query = "SELECT COUNT(*) AS mCnt FROM entities
					LEFT JOIN entity_data ON ed_entity=entity_id
					LEFT JOIN entity_data_lang ON edl_entity=entity_id AND edl_lang=1
					LEFT JOIN users ON user_id=entity_creator
					WHERE entity_parent=? AND entity_is_widget=0 ".$where;
				
				$results = $db->data($query, $data);
				if( count($results) > 0 )
				{
					$page = 1;
					if( isset($_GET["page"]) && is_string($_GET["page"]) && ctype_digit($_GET["page"]) )
					{
						$page = abs( (int)$_GET["page"] );
						
						if($page < 1)
						{
							$page = 1;
						}
					}
					
					$offset = ($page - 1) * PAGE_SIZE;
					
					$pages = ceil($results[0]["mCnt"] / PAGE_SIZE);
					if($pages > 1)
					{
						if( isset($customSortFields[$pid]) )
						{
							$orderBy = $customSortFields[$pid];
						}
						elseif( isset($templateCustormSortFields) && $templateId!==false && isset($templateCustormSortFields[$templateId]) )
						{
							$orderBy = $templateCustormSortFields[$templateId];
						}
						else
						{
							$orderBy = "entity_creation_date DESC";
						}
						
						$target = "_self";
						
						$tableId = "";//no drag and drop
					}
					else
					{
						if( isset($_GET["keyword"]) && is_string($_GET["keyword"]) && trim($_GET["keyword"])!=="" )
						{
							if( isset($customSortFields[$pid]) )
							{
								$orderBy = $customSortFields[$pid];
							}
							elseif( isset($templateCustormSortFields) && $templateId!==false && isset($templateCustormSortFields[$templateId]) )
							{
								$orderBy = $templateCustormSortFields[$templateId];
							}
							else
							{
								$orderBy = "entity_creation_date DESC";
							}
							
							$target = "_blank";
							
							$tableId = "";//no drag and drop
						}
						else
						{
							if( isset($customSortFields[$pid]) )
							{
								$orderBy = $customSortFields[$pid];
								
								$tableId = "";//no drag and drop
							}
							elseif( isset($templateCustormSortFields) && $templateId!==false && isset($templateCustormSortFields[$templateId]) )
							{
								$orderBy = $templateCustormSortFields[$templateId];
								
								$tableId = "";//no drag and drop
							}
							else
							{
								$orderBy = "entity_order DESC";
								
								$tableId = "report";//drag and drop
							}
							
							$target = "_self";
						}
					}
					
					$query = "SELECT * FROM entities
						LEFT JOIN entity_data ON ed_entity=entity_id
						LEFT JOIN entity_data_lang ON edl_entity=entity_id AND edl_lang=1
						LEFT JOIN users ON user_id=entity_creator
						WHERE entity_parent=? AND entity_is_widget=0 ".$where." ORDER BY ".$orderBy." LIMIT ?, ?";
						
					$data[] = $offset;
					$data[] = PAGE_SIZE;
					?>
						<div class="position-relative top-pad">
							<div class="top-section-funktional">
								<div class="burger">
									<svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path fill-rule="evenodd" clip-rule="evenodd" d="M2 12C2 11.4477 2.44772 11 3 11H21C21.5523 11 22 11.4477 22 12C22 12.5523 21.5523 13 21 13H3C2.44772 13 2 12.5523 2 12Z" fill="#3558A2"/>
										<path fill-rule="evenodd" clip-rule="evenodd" d="M2 6C2 5.44772 2.44772 5 3 5H21C21.5523 5 22 5.44772 22 6C22 6.55228 21.5523 7 21 7H3C2.44772 7 2 6.55228 2 6Z" fill="#3558A2"/>
										<path fill-rule="evenodd" clip-rule="evenodd" d="M2 18C2 17.4477 2.44772 17 3 17H21C21.5523 17 22 17.4477 22 18C22 18.5523 21.5523 19 21 19H3C2.44772 19 2 18.5523 2 18Z" fill="#3558A2"/>
									</svg>
								</div>
								<?php
									if($pid!=="0")
									{
										$parentEntity = getEntity($pid);
										if($parentEntity!==false)
										{
											?>
												<div class="south-east-wrap">
													<a class="south-east btn blue" href="<?php echo $_SERVER["SCRIPT_NAME"]."?sections&pid=".$parentEntity["entity_parent"] ?>"><span>Back</span> <img src="img/back.png" alt=""></a>
												</div>
											<?php
										}
									}
								?>
								<div class="search-and-add">
									<div class="search-wrap">
										<form action="<?php echo $_SERVER["SCRIPT_NAME"] ?>" method="get">
											<?php
												foreach($validQueryParams as $queryKey=>$queryValue)
												{
													if($queryKey!=="keyword")
													{
														?>
															<input type="hidden" name="<?php echo $queryKey ?>" value="<?php echo ($queryValue?htmlspecialchars($queryValue, ENT_QUOTES):'') ?>" />
														<?php
													}
												}
												
												if( isset($validQueryParams["keyword"]) )
												{
													$keyword = $validQueryParams["keyword"];
												}
												else
												{
													$keyword = "";
												}
											?>
											<input type="text" name="keyword" class="keyword" value="<?php echo ($keyword?htmlspecialchars($keyword, ENT_QUOTES):'')  ?>" placeholder="search..">
										</form>
									</div>
									<?php
										if( !isset($disableAddButtonIn) || !in_array($pid, $disableAddButtonIn)  )
										{
											?>
												<a class="btn green" href="<?php echo $_SERVER["SCRIPT_NAME"]."?addSection&pid=".$pid."&page=".$page ?>"><span>Add new section</span>  <img src="img/plus-square.svg" alt=""></a>
											<?php
										}
									?>
								</div>
							</div>
							<div class="oneMoreEntity">
								<div class="breadCrumb">
									<?php
									if($pid!=="0")
									{
										$treeItems = array();

										$iterPid = $pid;

										while($iterPid!==0)
										{
											$iterEntity = getEntity($iterPid);
											if($iterEntity!==false)
											{
												if($iterEntity[TITLE_FIELD])
												{
													$treeItems[] = $iterEntity[TITLE_FIELD];
												}
												else
												{
													$treeItems[] = $iterEntity["entity_creation_date"];
												}

												$iterPid = $iterEntity["entity_parent"];
											}
											else
											{
												$iterPid = 0;
											}
										}

										if(count($treeItems) > 0)
										{
											$treeItems = array_reverse($treeItems);

											$breadcrumb_title = array_pop($treeItems);

											echo implode(" > ", $treeItems);
										}
									}
									?>
								</div>
								<?php
									if( isset($breadcrumb_title) )
									{
										?>
											<div class="breadcrumb_title">
												<?php echo $breadcrumb_title ?>
											</div>
										<?php
									}
								?>
							</div>
							<section id="welcome" class="tm-section">
								<form action="<?php echo $_SERVER["SCRIPT_NAME"]."?".http_build_query($validQueryParams) ?>" method="post" class="contact-form white-background-color form-pading listing-form" name="orderForm">
									<table border="1" cellpadding="10" cellspacing="0" class="report" id="<?php echo $tableId ?>">
										<thead>
											<tr>
												<th class="pickColumn"><input class="pickBox" type="checkbox" onclick="toggleAll(this)"/></th>
												<th></th>
												<th>ID</th>
												<th>Title</th>
												<?php
													if( isset($additionalColumnsInSections[$pid]) )
													{
														foreach($additionalColumnsInSections[$pid] as $key=>$value)
														{
															?>
																<th><?php echo $value ?></th>
															<?php
														}
													}
													
													if( isset($templateAdditionalColumns) && $templateId!==false && isset($templateAdditionalColumns[$templateId]) )
													{
														foreach($templateAdditionalColumns[$templateId] as $key=>$value)
														{
															?>
																<th><?php echo $value ?></th>
															<?php
														}
													}
													
													if( isset($disableSubsectionsIn) && in_array($pid, $disableSubsectionsIn) )
													{
														$subSectionsEnabled = false;
													}
													elseif( isset($templateDisableSubsectionsIn) && in_array($templateId, $templateDisableSubsectionsIn) )
													{
														$subSectionsEnabled = false;
													}
													else
													{
														$subSectionsEnabled = true;
													}
													
													if($subSectionsEnabled)
													{
														?>
															<th>
																<?php
																	if( isset($subSectionLabels) && isset($subSectionLabels[$pid]) )
																	{
																		echo $subSectionLabels[$pid];
																	}
																	else
																	{
																		echo "Subsections";
																	}
																?>
															</th>
														<?php
													}
												?>
												<th>Created</th>
												<?php
													if(isset($widgetWhiteList))
													{
														if(in_array($pid, $widgetWhiteList) || ($parentEntity!==false && in_array($parentEntity["entity_type"], $widgetWhiteList, true)))
														{
															?>
																<th>
																	<?php
																		if( isset($subWidgetLabels) && isset($subWidgetLabels[$pid]) )
																		{
																			echo $subWidgetLabels[$pid];
																		}
																		else
																		{
																			echo "Widgets";
																		}
																	?>
																</th>
															<?php
														}
													}
												?>
												<th>Visibility</th>
												<th>Type</th>
												<th>Order</th>
												<th></th>
											</tr>
										</thead>
										<tbody>
											<?php
												$results = $db->data($query, $data);
												foreach($results as $result)
												{
													$entityId = $result["entity_id"];
													
													$template = searchTemplate($result["entity_type"]);
													
													$bgStyle = "";
													
													if(isset($result[IMG_FIELD]) && is_file($result[IMG_FIELD]) )
													{
														$bgStyle = "background-image:url('".$result[IMG_FIELD]."')";
													}
													?>
														<tr id="<?php echo $entityId ?>">
															<td class="pickColumn"><input class="pickBox" type="checkbox" onclick="innerPick(this)"/></td>
															<?php
																if($bgStyle) {
																	?>
																		<td class="imageColumn" style="<?php echo $bgStyle ?>"><div>&nbsp;</div></td>
																	<?php
																} else {
																	?>
																	<td class="imageColumn hidden" style="<?php echo $bgStyle ?>"><div>&nbsp;</div></td>
																	<?php
																}
															?>
															<td class="idColumn"><?php echo $result["entity_id"] ?></td>
															<td class="titleColumn">
																<?php
																	if( isset($titleFields) && isset($titleFields[$pid]) )
																	{
																		$titles = [];
																		
																		foreach($titleFields[$pid] as $fieldName)
																		{
																			$titles[] = $result[$fieldName];
																		}
																		
																		echo implode("&nbsp;", $titles);
																	}
																	elseif( isset($templateTitleFields) && isset($templateTitleFields[$templateId]) )
																	{
																		$titles = [];
																		
																		foreach($templateTitleFields[$templateId] as $fieldName)
																		{
																			$titles[] = $result[$fieldName];
																		}
																		
																		echo implode("&nbsp;", $titles);
																	}
																	else
																	{
                                                                        $title = '';
                                                                        if($result[TITLE_FIELD]){
                                                                            $title = $result[TITLE_FIELD];
                                                                        }

																		$shortVersion = mb_substr(strip_tags($title), 0, 250);
																		
																		echo $shortVersion;
																		
																		if($shortVersion!==strip_tags($title))
																		{
																			echo "...";
																		}
																	}
																?>
															</td>
															<?php
																if( isset($additionalColumnsInSections[$pid]) )
																{
																	foreach($additionalColumnsInSections[$pid] as $key=>$value)
																	{
																		?>
																			<td class="titleColumn"><?php echo $result[$key] ?></td>
																		<?php
																	}
																}
																
																if( isset($templateAdditionalColumns) && $templateId!==false && isset($templateAdditionalColumns[$templateId]) )
																{
																	foreach($templateAdditionalColumns[$templateId] as $key=>$value)
																	{
																		?>
																			<td class="titleColumn"><?php echo $result[$key] ?></td>
																		<?php
																	}
																}
																
																if($subSectionsEnabled)
																{
																	?>
																		<td>
																			<a href="<?php echo $_SERVER["SCRIPT_NAME"]."?sections&pid=".$entityId ?>" class="childLink" title="VIEW" target="<?php echo $target ?>">VIEW</a>
																		</td>
																	<?php
																}
															?>
															<td><?php echo $result["entity_creation_date"] ?></td>
															<?php
																if(isset($widgetWhiteList))
																{
																	if(in_array($pid, $widgetWhiteList) || ($parentEntity!==false && in_array($parentEntity["entity_type"], $widgetWhiteList, true)))
																	{
																		if($template!==false && isset($template["hasWidget"]) && $template["hasWidget"]===true)
																		{
																			?>
																				<td><a href="<?php echo $_SERVER["SCRIPT_NAME"]."?widgets&pid=".$entityId."&page=".$page ?>" class="childLink" title="View" target="<?php echo $target ?>">VIEW</a></td>
																			<?php
																		}
																		else
																		{
																			?><td style="text-align:center">-</td><?php
																		}
																	}
																}
															?>
															<td>
																<input type="hidden" name="orders[]" value="<?php echo $entityId ?>">
																<?php
																	if($result["entity_visible"]===0)
																	{
																		echo "<i>Hidden</i>";
																	}
																	elseif($result["entity_visible"]===1)
																	{
																		echo "Visible";
																	}
																?>
															</td>
															<td class="typeColumn">
																<?php
																	if($template!==false)
																	{
																		echo $template["title"];
																	}
																	else
																	{
																		echo $result["entity_type"];
																	}
																?>
															</td>
															<td><?php echo $result["entity_order"] ?></td>
															<td><a href="<?php echo $_SERVER["SCRIPT_NAME"]."?editSection&eid=".$entityId."&page=".$page ?>" class="linkButton btn blue" target="<?php echo $target ?>">EDIT  <img src="img/x-square.png" alt=""></a></td>
														</tr>
													<?php
												}
											?>
										</tbody>
									</table>
								</form>
								<?php
									if($pages > 1)
									{
										$paginationQueryParams = $validQueryParams;
										?>
											<form action="<?php echo $_SERVER["SCRIPT_NAME"] ?>" method="get" name="pagerForm">
												<?php
													foreach($validQueryParams as $queryKey=>$queryValue)
													{
														?>
															<input type="hidden" name="<?php echo $queryKey ?>" value="<?php echo ($queryValue?htmlspecialchars($queryValue, ENT_QUOTES):'') ?>" />
														<?php
													}
												?>
												<div class="myPagination align-center">
													<?php
														if($page > 1)
														{
															$paginationQueryParams["page"] = $page - 1;
															?>
																<a class="btn green" href="<?php echo $_SERVER["SCRIPT_NAME"]."?".http_build_query($paginationQueryParams) ?>">⇐</a>
															<?php
														}
													?>
													<div class="inputTd select-element">
														<select name="page" onchange="document.pagerForm.submit()" class="form-control">
															<?php
																for($i=1; $i<=$pages; $i++)
																{
																	$selected = "";
																	
																	if($i===$page)
																	{
																		$selected = "selected";
																	}
																	?>
																		<option <?php echo $selected ?> value="<?php echo $i ?>"><?php echo $i ?></option>
																	<?php
																}
															?>
														</select>
													</div>
													<div>
														<?php
															if($page < $pages)
															{
																$paginationQueryParams["page"] = $page + 1;
																?>
																	<a class="btn green" href="<?php echo $_SERVER["SCRIPT_NAME"]."?".http_build_query($paginationQueryParams) ?>">⇒</a>
																<?php
															}
														?>
													</div>
												</div>
											</form>
										<?php
									}
								?>
							</section>
						</div>
					<?php
				}
			}
		}
	}
	else
	{
		?><meta http-equiv="refresh" content="0; url=<?php echo $_SERVER["SCRIPT_NAME"]."?sections&pid=0" ?>"><?php
	}
?>