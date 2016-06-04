$ = new jQuery.noConflict();
$(document).ready(function(){
	$("#review_form form").submit(function(e){
		var rating_total = 0;
		var rating_count = 0;
		$(".single-rating").each(function(){
			rating_total += Number($(this).val());
			rating_count++;
		})
		$("#overall-rating").val(( rating_total / rating_count ));
		// e.preventDefault()
	})

	$(".woo-rich-rating p span a").click(function(){
		var par = $(this).parent().parent().parent();
		$(".single-rating", par).val($(this).text())
	})
})