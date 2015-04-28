<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>
            Invoice #<?php echo ($invoice->ref); ?>
		</title>
		<style type="text/css">

			body {
				font-family: Verdana, Geneva, sans-serif;
				margin-left: 35px;
				margin-right: 45px;
			}

			th {
				border: 1px solid #666666;
				background-color: #D3D3D3;
			}

			h2 {
				margin-bottom: 0px;
			}

			p.notop {
				margin-top: 0px;
			}

		</style>
	</head>
	<body>
		<table width="100%">
			<tr>
				<td width="60%" valign="top">
					<img style="max-width: 300px; width: auto;" width="300" src="<?php echo(($_SERVER['HTTPS']!==""?"https://":"http://").$_SERVER['SERVER_NAME'].$settings->bizlogo); ?>"/>
					<h2><?php echo ($settings->bizname); ?></h2>
					<p class="notop">
						<?php echo ($settings->bizaddress); ?><br />
						<?php echo ($settings->bizsuburb); ?>
						<?php echo ($settings->bizstate." ".$settings->bizpostcode." ".$settings->bizcountry); ?><br />
						<?php echo ($settings->biznumber); ?>
					</p>
				</td>
				<td width="40%" align="right" valign="top">
					<h2><?php echo ('Invoice'); ?></h2>
					<p class="notop">
						<?php echo ("Invoice # "); ?>
						<?php echo ($invoice->ref); ?><br />
						<?php echo('Invoice Date'); ?>:&nbsp;&nbsp;
						<?php echo (WposAdminUtilities::getDateFromTimeStamp($invoice->processdt, $settings->dateformat, false)); ?><br />
						<?php echo('Due Date'); ?>:&nbsp;&nbsp;
						<?php echo (WposAdminUtilities::getDateFromTimeStamp($invoice->duedt, $settings->dateformat, false)); ?>
					</p>
				</td>
			</tr>
		</table>
		<p>
			<?php echo ($customer->name); ?><br />
			<?php echo ($customer->address); ?><br />
            <?php echo ($customer->suburb); ?><br />
			<?php echo ($customer->state . ' ' . $customer->postcode . ' ' . $customer->country); ?><br />
		</p>
		<br />
		<table width="100%">
			<tr>
				<th width="15%">
					<?php echo ('Quantity'); ?>
				</th>
				<th width="40%">
					<?php echo ('Item'); ?>
				</th>
				<th width="15%" align="right">
					<?php echo ('Unit'); ?>
				</th>
				<th width="15%" align="right">
					<?php echo ('Tax'); ?>
				</th>
				<th width="15%" align="right">
					<?php echo ('Cost'); ?>
				</th>
				</tr>
			<?php foreach ($invoice->items as $value) { ?>
			<tr>
					<td style="vertical-align: top;" align="center"><?php echo ($value->qty); ?></td>
					<td style="vertical-align: top;"><?php echo ("<strong>".$value->name.":</strong><br>".nl2br($value->desc)); ?></td>
					<td style="vertical-align: top;" align="right"><?php echo (WposAdminUtilities::currencyFormat($settings->curformat, $value->unit)); ?></td>
					<td style="vertical-align: top;" align="right"><?php echo ($taxes[$value->taxid]->name." ".WposAdminUtilities::currencyFormat($settings->curformat, $value->tax)); ?></td>
					<td style="vertical-align: top;" align="right"><?php echo (WposAdminUtilities::currencyFormat($settings->curformat, $value->price)); ?></td>
			</tr>
			<?php } ?>
			<tr>
				<td colspan="2"></td>
				<td colspan="3"><hr /></td>
			</tr>
			<tr>
				<td colspan="4" align="right">
					<?php echo ('Subtotal: '); ?>
				</td>
				<td align="right">
					<?php echo (WposAdminUtilities::currencyFormat($settings->curformat, $invoice->subtotal)); ?>
				</td>
			</tr>
			<?php if (isset($invoice->taxdata)) foreach ($invoice->taxdata as $key=>$value) { ?>
			<?php if ($value) { ?>
			<tr>
				<td colspan="4" align="right">
					<?php echo ($taxes[$key]->name." (".$taxes[$key]->value."%):"); ?>
				</td>
				<td align="right">
					<?php echo (WposAdminUtilities::currencyFormat($settings->curformat, $value)); ?>
				</td>
			</tr>
			<?php } ?>
			<?php } ?>
            <?php if ($invoice->discount!=0){ ?>
            <tr>
				<td colspan="4" align="right">
					<?php echo ("Discount (".$invoice->discount."%): "); ?>
				</td>
				<td align="right">
					<?php echo ("-".WposAdminUtilities::currencyFormat($settings->curformat, $invoice->discountval)); ?>
				</td>
			</tr>
            <?php } ?>
			<tr>
				<td colspan="4" align="right">
					<?php echo ("Grand Total: "); ?>
				</td>
				<td align="right">
					<?php echo (WposAdminUtilities::currencyFormat($settings->curformat, $invoice->total)); ?>
				</td>
			</tr>
			<tr>
				<td colspan="4" align="right">
					<?php echo ('Amount Paid: '); ?>
				</td>
				<td align="right">
					<?php echo (WposAdminUtilities::currencyFormat($settings->curformat, ($invoice->total-$invoice->balance))); ?>
				</td>
			</tr>
			<tr>
				<td colspan="4" align="right">
					<?php echo ('Total Due: '); ?>
				</td>
				<td align="right">
					<?php echo (WposAdminUtilities::currencyFormat($settings->curformat, $invoice->balance)); ?>
				</td>
			</tr>
		</table>
        <div style="margin-top: 20px;">
            <?php echo($settings->payinst); ?>
        </div>
	</body>
</html>