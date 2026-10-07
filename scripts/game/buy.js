$(function () {
    $('#count_calculator input[type=text]').keyup(function () {
        countDots();
    });
    $('form').submit(function () {
        DotsToCount();
    });
});

function DotsToCount() {
    $('#count_calculator input[type=text]').val(function (i, old) {
        return old.replace(/[^[0-9]|\.]/g, '');
    });
}

function countDots() {
    $('#count_calculator input[type=text]').val(function (i, old) {
        return NumberGetHumanReadable(old.replace(/[^[0-9]|\.]/g, ''));
    });
}

function updateVars(ID)
{	
	var Element 	= ID;
	$('#Element').val(Element);
	$('#img').attr('src', $('#img').data('src')+Element+'.gif');
	var curLvl = Number(CostInfo[Element][0]);
	var baseCost = Number(CostInfo[Element][2]);
	var factor = Number(CostInfo[Element][3]);
	var nextPrice = baseCost;
	if ($('#total_price_factor').length > 0) {
		nextPrice = Math.max(15, Math.ceil(baseCost * Math.pow(factor, curLvl)));
	}
	$('#price').text(NumberGetHumanReadable(nextPrice));
	$('#traderHead').text(CostInfo[Element][1]);
	$('#batn').show();
	Reset();
}

function Total()
{
    DotsToCount();
	var Count	= $('#count').val();
	
	if(isNaN(Count) || Count < 0) {
		$('#count').val(0);
		Count = 0;
	}
	countDots();
	var Element 	= $('#Element').val();
	if (!Element || !CostInfo[Element]) return;
	
	if ($('#total_price_factor').length > 0) {
		var curLvl = Number(CostInfo[Element][0]);
		var baseCost = Number(CostInfo[Element][2]);
		var factor = Number(CostInfo[Element][3]);
		var total = 0;
		for (var i = 1; i <= Number(Count); i++) {
			total += Math.max(15, Math.ceil(baseCost * Math.pow(factor, curLvl + i - 1)));
		}
		$('#total_price_factor').text(NumberGetHumanReadable(total));
	} else {
		$('#total_price_count').text(NumberGetHumanReadable(Math.ceil(CostInfo[Element][2] * Count)));
	}
}

function Reset()
{
	$('#count').val(0);
	$('#total_price_factor').text(0);
    $('#total_price_count').text(0);
}