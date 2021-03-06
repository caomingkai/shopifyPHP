<?php
//-----## 0 ## find out the main Theme.liquid, and its ID
    $themes = $shopify->Theme->get();
    $numsOfThemes = count($theme);
    $themeID = 0;
    foreach( $themes as $oneTheme ){
      if($oneTheme['role'] === 'main'){
        $themeID = $oneTheme['id'];
        break;
      }
    }

//-----## 1 ## insert bundleCheck.liquid to shopify admin Snippet
// the code snippet to be inserted
$codeInject = '
<script type="text/javascript">
function load(){
//=================================================================================================
//--  ## 0 ## convert all products including shadow product into cartOriginData -->
//=================================================================================================
				{% assign cartAddedData  = "" %}
	  		{% assign cartBeginData  = "" %}
        {% assign cartFinalData  = "" %}
        {% assign cartOriginData = "" %}
    //-------------------------------------------------------------------------------------------------------
    // cartOriginData : First, it is origin variants corresponding to variants( origin or shadow ) in cart.
    //                  Later, as shadow variants added to the cartAddedData, will delete variants from it.
    // cartAddedData:   Based on bundle info, add shadow variants into this cartAddedData
    // cartFinalData:   In the last, combine cartOriginData and cartAddedData; compare with cartBeginData to render what to delete/add
    // cartBeginData:   Record the beginning data of cart, used to compared with cartFinalData to make least modification.
    //-------------------------------------------------------------------------------------------------------
        {% for item in cart.items %}
			      {% assign variantID = item.variant_id|append:"" %}

            {% assign itemOriginVPCStr = item.variant.metafields.shadowToOrigin[variantID]   %}
						{% if itemOriginVPCStr  %}
                {% assign cartOriginVPCStr = itemOriginVPCStr  %}
            {% else %}
								//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
								{% assign cartOriginCStr = "" %}
								{% for collection in item.product.collections %}
										{% assign cartOriginCStr = cartOriginCStr | append: "," | append: collection.id   %}
								{% endfor %}
								{% assign cartOriginCStr = cartOriginCStr | remove_first: ","  %}
								//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
          			{% assign cartOriginVPCStr = item.variant_id |append: ":" |append: item.product_id |append: ":" |append: cartOriginCStr %}
            {% endif %}
  					{% assign cartOriginData = cartOriginData | append:cartOriginVPCStr  | append: ":"| append: item.quantity | append: "\n"  %}
            {% assign cartBeginData  = cartBeginData  | append: item.variant_id  | append: ":"| append: item.quantity | append: "\n" %}
        {%endfor%}

//=================================================================================================
//--  ## 1 ## merge common items in cartOriginData, update into new cartOriginData -->
//=================================================================================================
      	{% assign cartOriginArr = cartOriginData | strip | split: "\n" | sort %}
//document.getElementById("bundle00").innerHTML = "<br>cartOriginData:"+"<br>"+"{{cartOriginArr[0]}}" + "<br>" +"{{cartOriginArr[1]}}"+"<br>" +"{{cartOriginArr[2]}}";
				{% assign cartOriginDataTemp = "" %}
				{% for itemStr in cartOriginArr %}
  					{% assign itemArr = itemStr | split: ":" %}
  					{% assign cartOriginArrTemp = cartOriginDataTemp | strip | split: "\n" %}
  					{% assign cartOriginArrLastTemp = cartOriginArrTemp|last %}
    			  {% assign cartOriginArrLastTempArr = cartOriginArrLastTemp| split: ":" %}
				    {% assign cartOriginDataTemp_1 = "" %}
// alert("cartOriginDataTemp:"+"{{cartOriginDataTemp}}"+"---"+"cartOriginArrLastTemp:"+"{{cartOriginArrLastTemp}}");
// alert("{{itemArr[0]}}"+"---"+"{{cartOriginArrLastTempArr[0] }}");
  					{% if itemArr[0] == cartOriginArrLastTempArr[0] %}
  						  {% assign loopLimit = cartOriginArrTemp | size | minus: 1 %}
  						  {% for cartOriginItemTemp  in cartOriginArrTemp limit:loopLimit %}
                    {% assign cartOriginDataTemp_1 = cartOriginDataTemp_1 | append: cartOriginItemTemp |append:"\n" %}
                {% endfor %}
  						  {% assign quantityNew = itemArr[3] | plus: cartOriginArrLastTempArr[3] %}
  						  {% assign cartOriginStrLastTemp = itemArr[0]| append: ":" |append: itemArr[1]| append: ":" | append: itemArr[2] | append: ":" | append: quantityNew  %}
        				{% assign cartOriginDataTemp_1 = cartOriginDataTemp_1 | append: cartOriginStrLastTemp |append:"\n" %}
            {% else %}
        				{% assign cartOriginDataTemp_1 = cartOriginDataTemp_1 | append: cartOriginDataTemp | append: itemStr |append:"\n" %}
            {% endif %}
// alert("{{cartOriginDataTemp_1}}");
            {% assign cartOriginDataTemp = cartOriginDataTemp_1 %}
  			{% endfor %}
  			{% assign cartOriginData = cartOriginDataTemp  %}
//document.getElementById("bundle").innerHTML = "<br>cartOriginData/cartBeginData"+"<br>"+"{{cartOriginData}}" + "<br>" +"{{cartBeginData}}";

//=================================================================================================
//--  ## 2 ## check each bundle to see if there exist matches in cart. bundle is one bundle_item containing info -->
//=================================================================================================
        {% assign Bundles = shop.metafields.bundleInfo.bundleDetail |  newline_to_br | strip_newlines | split: "<br />" | sort  %}
				{% for bundleStr in Bundles %}
// alert("{{bundleStr}}");
            {% assign bundleType = bundleStr | strip | split:"&" | first | split:"*" | last  %}
						{% assign bundleInfo = bundleStr | strip | split:"@" | last %}

      			{% assign bundle = bundleInfo | strip | split:"#" %}
            {% assign BDID = bundle | first %}
            {% assign BD = bundle | last %}
            {% assign BDArray = BD | split:","  %}
            {% assign min = 1000000 %}
            {% assign cartOriginArr = cartOriginData | strip | split: "\n"  %}


//--  ## 3 ## BDOne is one pair of originProductID:discount  -->
            {% for BDOne in BDArray %}
                {% assign BDOnePair = BDOne | split:":"   %}
                {% assign BDOneId = BDOnePair | first  %}
                {% assign cnt = 0 %}

//--  ## 4 ## find out if there exists such bundle pattern in cart depending on cnt?=0 , meanwhile, record quantity of this combo -->
                {% for itemStr in cartOriginArr  %}
                    {% assign itemArr = itemStr | split: ":"  %}
//~~~~~~~~~~~~~~~~~~~~~depend on bundle type to determine if bundle exist~~~~~~~~
										{% if  bundleType == "0" %}
// alert("bundleType == 0");
			                  {% if itemArr[1] ==  BDOneId  %}
			                      {% assign cnt = cnt | plus: itemArr[3]  %}
			                  {% endif %}
										{% else %}
// alert("bundleType == 1");
												{% assign flag = false  %}
												{% assign colletionIdArr = itemArr[2] | split: ","  %}
												{% for colletionId in colletionIdArr  %}
														{% if colletionId == BDOneId  %}
																{% assign flag = true  %}
																{% break %}
														{% endif %}
												{%endfor%}
												{% if flag == true  %}
														{% assign cnt = cnt | plus: itemArr[3]  %}
												{% endif %}
// alert("{{cnt}}");
										{% endif %}
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
                {%endfor%}
// alert("cnt:"+"{{cnt}}");
                {% if cnt == 0  %}
                    {% assign min = 1000000 %}
			 	            {% break %}
                {% else %}
                    {% if cnt < min  %}
                        {% assign min = cnt %}
                    {% endif %}
                {% endif %}
            {%endfor%}
// alert("{{min}}");


//--  ## 5 ## if there exist such bundle, delete origin variant in cartOriginArr, record added shadow variant in shadowFinalData  -->
//--  Meanwhile, record origin variant after deletion in originFinalData. Then combine shadowFinalData and originFinalData into cartFinalData  -->
      			{% if min != 1000000 %}
                {% for BDOne in BDArray %}
                    {% assign BDOnePair = BDOne | split:":"   %}
                    {% assign BDOneId = BDOnePair | first  %}

                    {% assign minTemp = min %}
                    {% assign cartOriginDataTemp = cartOriginData %}

                    {% for itemStr in cartOriginArr  %}
                        {% assign itemArr = itemStr | split: ":"  %}
                        {% assign cartOriginArrTemp = cartOriginDataTemp | strip | split: "\n" | sort  %}
//~~~~~~~~~~~~~~~~~~~~~If any type of collection or product matchs, then update cartOriginData and cartAddedData ~~~~~~~~
												{% assign itemMatchFlag = false  %}
					              {% if itemArr[1] ==  BDOneId  %}
					                      {% assign itemMatchFlag = true  %}
					              {% endif %}

												{% assign flag = false  %}
												{% assign colletionIdArr = itemArr[2] | split: ","  %}
												{% for colletionId in colletionIdArr  %}
														{% if colletionId == BDOneId  %}
																{% assign flag = true  %}
																{% break %}
														{% endif %}
												{%endfor%}
												{% if flag == true  %}
														{% assign itemMatchFlag = true  %}
												{% endif %}
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
                        {% if itemMatchFlag == true  %}
//--  ## 6 ##  find corresponding shadow variant for origin variant, based on bundleID  -->
                            {% assign originToShadowArr = shop.metafields.originToShadow[itemArr[0]] | split: ","  %}
                            {% for OTSStr in originToShadowArr  %}
                                {% assign OTSPair = OTSStr | split: ":"  %}
                                {% if BDID == OTSPair[0]  %}
                                    {% assign shadowVarID = OTSPair[1]  %}
                                {% endif %}
                            {%endfor%}

//--  ## 7 ##  delete  origin variant in cartOriginArr, based on the found shadowID, record updated variant in originFinalData and added shadow variant in shadowFinalData  -->
                            {% assign itemNeedChange = itemArr[0]  %}
		                        {% assign itemQuantity =   itemArr[3] | plus:0 %}
                            {% if itemQuantity <= minTemp  %}
//--  ## 8 ##  update cartOriginArr, since there are origin deleted, and the quantity are now changed -->
                                {% assign temp = "" %}
                                {% for itemStrTemp in cartOriginArrTemp  %}
      															{% assign itemArrTemp = itemStrTemp | split:":" %}
// alert("{{itemArrTemp[0]}}"+":"+"{{itemNeedChange}}");
                                    {% if itemArrTemp[0] != itemNeedChange  %}
                                        {% assign temp = temp | append: itemStrTemp | append:"\n" %}
                                    {% endif %}
                                {%endfor%}
                                {% assign cartOriginDataTemp = temp %}
                                {% assign cartAddedData = cartAddedData | append: shadowVarID | append:":"| append: itemArr[3] | append: "\n" %}
                                {% assign minTemp = minTemp | minus: itemArr[3] %}
                            {% else %}
                                {% assign quantityNew = itemArr[3] | minus: minTemp  %}
//--  ## 8 ##  update cartOriginArr, since there are origin deleted, and the quantity are now changed -->
                                {% assign temp = "" %}
                                {% for itemStrTemp in cartOriginArrTemp  %}
                                {% assign itemArrTemp = itemStrTemp | split:":" %}
                                    {% if itemArrTemp[0] != itemNeedChange  %}
                                        {% assign temp = temp | append: itemStrTemp | append:"\n" %}
                                    {% else %}
                                        {% assign itemStrChanged = itemArr[0] | append :":"| append : itemArr[1] | append :":"| append : itemArr[2]| append :":"| append: quantityNew  %}
                                        {% assign temp = temp | append: itemStrChanged | append:"\n" %}
                                    {% endif %}
                                {%endfor%}
                                {% assign cartOriginDataTemp = temp %}
                                {% if minTemp > 0  %}
								                    {% assign cartAddedData = cartAddedData | append: shadowVarID | append:":"| append: minTemp | append: "\n" %}
                                {% endif %}
						                {% endif %}
                        {% endif %}
                    {%endfor%}
                    {% assign cartOriginData = cartOriginDataTemp  %}
                    {% assign cartOriginArr = cartOriginData | strip | split: "\n" | sort %}
                {%endfor%}
            {% else %}

            {% endif %}

        {%endfor%}

//=================================================================================================
//--  ## 9 ##  此处：将shadowFinalData与originFinalData合成cartFinalData（quantity==0的舍弃） -->
//=================================================================================================
//document.getElementById("bundle0").innerHTML = "<br>cartOriginData/cartAddedData"+"<br>"+"{{cartOriginData}}" + "<br>" +"{{cartAddedData}}";
            {% for originItemStr in cartOriginArr  %}
                {% assign originItemTriple = originItemStr | split: ":" %}
                {% assign originItemTriple = originItemTriple[0] | append: ":" | append: originItemTriple[3] | append:"\n"  %}
                {% assign cartFinalData = cartFinalData | append: originItemTriple  %}
            {%endfor%}
// //document.getElementById("bundle0").innerHTML = "<br>cartFinalData"+"<br>"+"{{cartFinalData}}";

            {% assign cartFinalData = cartFinalData | append: cartAddedData %}

//=================================================================================================
//--  ## 10 ##  compare "cartBeginData" and "cartFinalData", make delete/add AJAX call based on comparason result -->
//=================================================================================================
        {% assign cartBeginData = cartBeginData |strip | split:"\n" | sort | join: "\n" %}
        {% assign cartFinalData = cartFinalData |strip | split:"\n" | sort | join: "\n" %}
//document.getElementById("bundle1").innerHTML = "<br>cartBeginData/cartFinalData"+"<br>"+"{{cartBeginData}}" + "<br>" +"{{cartFinalData}}";
        {% if cartBeginData != cartFinalData %}
            {% assign cartBeginArr = cartBeginData | strip | split:"\n" %}
            {% assign cartFinalArr = cartFinalData | strip | split:"\n" %}
//--  ## 11 ##  find element in cartBeginArr but not in cartFinalArr : these are origin variants to be deleted -->
            {% assign toBeDeleted = "" %}
            {% for cartBeginItem in cartBeginArr %}
                {% assign flag = false %}
                {% for cartFinalItem in cartFinalArr %}
                    {% if cartFinalItem == cartBeginItem %}
                        {% assign flag = true %}
                    {% endif %}
                {% endfor %}
                {% if flag == false %}
                    {% assign toBeDeleted = toBeDeleted | append: cartBeginItem | append:"\n" %}
                {% endif %}
            {% endfor %}

//--  ## 12 ##  find element in cartFinalArr but not in cartBeginArr : these are shadow variants to be added -->
            {% assign toBeAdded = "" %}
            {% for cartFinalItem in cartFinalArr %}
                {% assign flag = false %}
                {% for cartBeginItem in cartBeginArr %}
                    {% if cartBeginItem == cartFinalItem %}
                        {% assign flag = true %}
                    {% endif %}
                {% endfor %}

                {% if flag == false %}
                    {% assign toBeAdded = toBeAdded | append: cartFinalItem | append:"\n" %}
                {% endif %}
            {% endfor %}
            {% assign toBeDeletedArr = toBeDeleted | strip | split:"\n" %}
            {% assign toBeAddedArr   = toBeAdded   | strip | split:"\n" %}

//--  ## 13 ##  Make AJAX call to delete/add arr, based on toBeDeletedArr/toBeAddedArr -->
//document.getElementById("bundle2").innerHTML = "toBeAdded/toBeDeleted"+ "<br>"+"{{toBeAdded}}" + "<br>" +"{{toBeDeleted}}";

            {% for toBeDeleteItemStr in toBeDeletedArr %}
            		{% assign toBeDeleteItem = toBeDeleteItemStr | split:":" %}
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        //document.getElementById("deleteArea").innerHTML =
                          "Delete SUCCEED!";
                    }
                };
                xhttp.open("POST","/cart/change.js", false);
                xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xhttp.send("id={{toBeDeleteItem[0]}}&quantity=0");
            {% endfor %}

            {% for toBeAddedItemStr in toBeAddedArr %}
            	{% assign toBeAddedItem = toBeAddedItemStr | split:":" %}
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        //document.getElementById("addArea").innerHTML =
                          "ADD SUCCEED!";
                    }
                };
                xhttp.open("POST", "/cart/add.js", false);
                xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xhttp.send("id={{toBeAddedItem[0]}}&quantity={{toBeAddedItem[1]}}");
            {% endfor %}
                     	location.reload();
        {% else %}
//                         alert("No change, So no need to call AJAX!");
        {% endif %}
}

window.onload = load;

</script>
';
//-------------------------------------------------------------------------------
    $para = array(
      "key" => "snippets/bundleCheck.liquid",
      "value" => $codeInject
    );
    $bundleCheckSnippet = $shopify->Theme($themeID)->Asset->put($para) ;

    // echo '<h1> 1. snippets/bundleCheck.liquid  Updated successfully </h1>' .  "\n";
    // echo '<br>' .  "\n";




//-----## 2  ## insert the statement: {% include "bundleCheck" %} to "theme.liquid" file  -->


    // find out theme.liquid, based on themeID already got
    $para = array(
      "asset[key]" => "layout/theme.liquid",     // Note: the key is 'asset[key]', NOT 'key' !
    );
    $themeContent = $shopify->Theme($themeID)->Asset->get($para)['asset']['value'] ;


    // find out </body> tag, in order to insert into this statement: "{% include 'bundleCheck' %}"
    // only if there doesn't exist such insertion before, we insert this code snippet. Otherwise, do nothing.
    $codeExistPost = strpos( $themeContent, "{% include 'bundleCheck' %}");
    if( $codeExistPost === false ){
        $posOfBodyEnd = strpos( $themeContent, '</body>');
        $statementInject =  " {% if template == 'cart' %}{% include 'bundleCheck' %}{% endif %}"."\n";
        $themeContentNew = substr_replace( $themeContent , $statementInject,  $posOfBodyEnd, 0 );


        // insert statement  "{% include 'bundleCheck' %}", right after <head> tag
        $para = array(
          "key" => "layout/theme.liquid",
          "value" => $themeContentNew
        );
        $themeContentNew = $shopify->Theme($themeID)->Asset->put($para) ;

                                                    // echo '<h1>$shopify below : </h1>' .  "\n";
                                                    // echo "<pre>";
                                                    // print_r ($themeContentNew);
                                                    // echo "</pre>";
                                                    // echo '<p> ------------------------  </p>' .  "\n";
    }else{
                                                    // echo '<h1> 2. already injected </h1>' .  "\n";
    }



?>
