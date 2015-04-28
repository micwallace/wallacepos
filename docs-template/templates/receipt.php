<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>
			<?php echo("Payment Receipt"); ?>
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

		</style>
	</head>
	<body>
		<table width="100%">
			<tr>
				<td width="60%" valign="top">
					<img src="http://<?php echo($_SERVER['SERVER_NAME']); ?>/woms/tuxfinalxsmall.png"/>
					<h2><?php echo ($business['name']); ?></h2>
					<p class="notop">
						<?php echo ($business['name']); ?><br />
						<?php echo ($business['address']); ?><br />
						<?php echo ($business['suburb']); ?>
						<?php echo ($business['state']." ".$business['postcode']." ".$business['country']); ?><br />
						<?php echo ($business['taxid']); ?>
					</p>
				</td>
				<td width="40%" align="right" valign="top" rowspan="2">
					<h1 style="text-align: right;"><?php echo('Payment Receipt'); ?></h1>
				</td>
			</tr>
            <tr>
				<td width="40%" valign="top">
					<?php echo ($invoice['clientfname']); echo(($invoice['clientlname']!="") ? " ".$invoice['clientlname'] : ""); ?><br />
					<?php echo ($invoice['clientaddress']); ?><br />
            		<?php echo ($invoice['clientsuburb']); ?><br />
					<?php echo ($invoice['clientstate'] . ' ' . $invoice['clientpostcode'] . ' ' . $invoice['clientcountry']); ?><br />
				</td>
			</tr>
		</table>
		<br />
		<table width="100%">
			<tr>
				<th style="width: 20%;"><?php echo ('Date'); ?></th>
				<th style="width: 20%;"><?php echo ('Invoice #'); ?></th>
				<th style="width: 15%;"><?php echo ('Method'); ?></th>
                <th style="width: 30%;"><?php echo ('Notes'); ?></th>
				<th style="width: 15%;"><?php echo ('Amount'); ?></th>
			</tr>
			<?php foreach ($payments as $value) { ?>
			<tr>
				<td style="text-align: center;"><?php echo ($value['date']); ?></td>
				<td style="text-align: center;"><?php echo ($value['invoiceid']); ?></td>
				<td><?php echo ($value['method']); ?></td>
                <td><?php echo ($value['notes']); ?></td>
				<td><div style="text-align: right; margin-right: 10px;"><?php echo($value['amount']); ?></div></td>
			</tr>
			<?php } ?>
			<tr>
				<td colspan="4"><div style="text-align: right;"><strong><?php echo('Total:'); ?></strong></div></td>
				<td><div style="text-align: right; margin-right: 10px;"><strong><?php echo ($invoice['paid']); ?></strong></div></td>
			</tr>
		</table>

	</body>
</html>