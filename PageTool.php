<?php
//假设有$num=100条数据，每页显示10条数据,判断总共有多少页
//		$num=100;
//		$pageNum=$num/10;
//		$pgs 当前是第几页

//分页模块
class PageTool {
	
	function pagelist($pageNum,$cPage,$pgs)
    {
		$str="";
		//当前页的前四页
		for($i=$pageNum;$i>=1;$i--){
			if(($cPage-$i)>=1){
				$str.="<a href='page.php?pgs=".($cPage-$i)."'>".($cPage-$i)."</a>&nbsp;";
			}
		}
		//当前页
		if($cPage>=1){
			$str.="&nbsp;".$cPage."&nbsp;";
		}
		//当前页的后四页
		for($i=1;$i<=$pageNum;$i++){
			if(($cPage+$i)<=$pgs){
				$str.="<a href='page.php?pgs=".($cPage+$i)."'>".($cPage+$i)."</a>&nbsp;";
			}
		}
		return $str;
	}
	//上一页和首页
	function pagePrev($cPage){
		$cPage=$cPage>1?$cPage:1;
		if($cPage>1){
			return "<a href='page.php?pgs=1'>首页</a>&nbsp;"."<a href='page.php?pgs=".($cPage-1)."'>上一页</a>&nbsp";
		}

	}
	//下一页和末页
	function pageNext($cPage,$pgs){
		$cPage=$cPage<$pgs?$cPage:$pgs;
		if($cPage<$pgs){
			return "<a href='page.php?pgs=".($cPage+1)."'>下一页</a>&nbsp;"."<a href='page.php?pgs=".($pgs)."'>末页</a>";
		}
		return '';
	}
	function  showPage($pageNum,$cPage,$pgs){
		return $this->pagePrev($cPage)."&nbsp;".$this->pagelist($pageNum,$cPage,$pgs)."&nbsp;".$this->pageNext($cPage, $pgs);
	}
	
}
// 	$page=new pageDiv();
// 	$page->showPage(4, isset($_GET["pgs"])?$_GET["pgs"]:1,ceil($num/4));


?>