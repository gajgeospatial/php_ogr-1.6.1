 <BODY>
<HTML>
<PRE>
<?php

/******************************************************************************
 * $Id$
 *
 * Project:  PHP Interface for OGR C API
 * Purpose:  Test program for PHP/OGR module.
 * Author:   Normand Savard, nsavard@dmsolutions.ca
 *
 ******************************************************************************
 * Copyright (c) 2003, DM Solutions Group Inc
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *****************************************************************************/

   $eErr = OGR_SpatialFilter_main();

   if ($eErr != OGRERR_NONE)
   {
      printf("Some errors were reported (error %d)\n", $eErr);
   }
   else
   {
      printf("ogr_spatialfilter completed with no errors.\n");
   }

/************************************************************************/
/*                       OGR_SpatialFilter_main()                       */
/************************************************************************/
function OGR_SpatialFilter_main()
{
   /*Assigning initial value.*/
    $strFormat = "ESRI Shapefile";
    $strDataSource = NULL;
    $strDestDataSource = NULL;
    $strWhere = NULL;
    $hSpatialFilter = NULL;

/* -------------------------------------------------------------------- */
/*      Register format(s).                                             */
/* -------------------------------------------------------------------- */
    OGRRegisterAll();

/* -------------------------------------------------------------------- */
/*      Processing command line arguments.                              */
/* -------------------------------------------------------------------- */

    $numArgs = count($_SERVER["argv"]);

    for( $iArg = 1; $iArg < $numArgs; $iArg++ )
    {
        if( !strcasecmp($_SERVER["argv"][$iArg], "-f") && $iArg < $numArgs-1 )
        {
            $strFormat = $_SERVER["argv"][++$iArg];
            printf("Format = %s\n", $strFormat);
        }
        /* Construct a spatial filter.*/

        else if( $_SERVER["argv"][$iArg] == "-spat" 
                 && $_SERVER["argv"][$iArg+1] != NULL 
                 && $_SERVER["argv"][$iArg+2] != NULL 
                 && $_SERVER["argv"][$iArg+3] != NULL 
                 && $_SERVER["argv"][$iArg+4] != NULL )
        {
            $hSpatialFilter = OGR_G_CreateGeometry(wkbLinearRing);
            OGR_G_AddPoint($hSpatialFilter, floatval($_SERVER["argv"][$iArg+1]),
                           floatval($_SERVER["argv"][$iArg+2]), 0.0 );

            OGR_G_AddPoint($hSpatialFilter, floatval($_SERVER["argv"][$iArg+1]),
                           floatval($_SERVER["argv"][$iArg+4]), 0.0 );
            OGR_G_AddPoint($hSpatialFilter,  floatval($_SERVER["argv"][$iArg+3]), 
                           floatval($_SERVER["argv"][$iArg+4]), 0.0 );
            OGR_G_AddPoint($hSpatialFilter, floatval($_SERVER["argv"][$iArg+3]), 
                           floatval($_SERVER["argv"][$iArg+2]), 0.0 );
            OGR_G_AddPoint($hSpatialFilter, floatval($_SERVER["argv"][$iArg+1]), 
                           floatval($_SERVER["argv"][$iArg+2]), 0.0 );
            printf("Xmin = %s Ymin = %s Xmax = %s Ymax = %s\n", 
                   $_SERVER["argv"][$iArg+1], $_SERVER["argv"][$iArg+2],
                   $_SERVER["argv"][$iArg+3], $_SERVER["argv"][$iArg+4]);

            $iArg += 4;
        }

        /* Construct an attribute filter with where.  
           Example: $strWhere = "Pop_1981 <= 1000000" */

        else if( $_SERVER["argv"][$iArg] == "-where" 
                 && $_SERVER["argv"][$iArg+1] != NULL )
        {
            $strWhere = $_SERVER["argv"][++$iArg];
            printf("where = %s\n", $strWhere);
        }
        else if( $_SERVER["argv"][$iArg][0] == '-' )
        {
            Usage();
        }
        else if( $strDestDataSource == NULL )
        {
            $strDestDataSource = $_SERVER["argv"][$iArg];
            printf("DestDataSource = %s\n", $strDestDataSource);
        }
        else if( $strDataSource == NULL )
        {
            $strDataSource = $_SERVER["argv"][$iArg];
            printf("DataSource = %s\n", $strDataSource);
        }
    }

    if( $strDataSource == NULL )
        Usage();



/* -------------------------------------------------------------------- */
/*      Open data source.                                               */
/* -------------------------------------------------------------------- */

    $hSFDriver = NULL;
    $hDS = OGROpen( $strDataSource, FALSE, $hSFDriver );

/* -------------------------------------------------------------------- */
/*      Report failure                                                  */
/* -------------------------------------------------------------------- */
    if( $hDS == NULL )
    {
       
        printf( "FAILURE:\nUnable to open datasource `%s' with the following drivers:\n",
                $strDataSource );

        for( $iDriver = 0; $iDriver < OGRGetDriverCount(); $iDriver++ )
        {
            printf( "  -> %s\n", OGR_DR_GetName(OGRGetDriver($iDriver)) );
        }

        return OGRERR_FAILURE;
    }

/* -------------------------------------------------------------------- */
/*      Find the output driver.                                         */
/* -------------------------------------------------------------------- */
    for( $iDriver = 0;
         $iDriver < OGRGetDriverCount() && $hSFDriver == NULL;
         $iDriver++ )
    {
        if( !strcasecmp(OGR_DR_GetName(OGRGetDriver($iDriver)) , $strFormat) )
        {
            $hSFDriver = OGRGetDriver($iDriver);
        }
    }

    if( $hSFDriver == NULL )
    {
        printf( "Unable to find driver `%s'.\n", $strFormat );
        printf( "The following drivers are available:\n" );
        
        for( $iDriver = 0; $iDriver < OGRGetDriverCount(); $iDriver++ )
        {
            printf( "  -> %s\n", OGR_DR_GetName(OGRGetDriver($iDriver)) );
        }

        return OGRERR_FAILURE;
    }

    if( !OGR_Dr_TestCapability( $hSFDriver, ODrCCreateDataSource ) )
    {
        printf( "%s driver does not support data source creation.\n",
                $strFormat );
        return OGRERR_FAILURE;
    }

/* -------------------------------------------------------------------- */
/*      Create the output data source.                                  */
/* -------------------------------------------------------------------- */

   /*Uncomment and add options here. */
 /* $aoptions[0] = 'option1';
    $aoptions[1] = 'option2';
    $hODS = OGR_Dr_CreateDataSource( $hSFDriver, $strDestDataSource, $aoptions );
*/

    /* Or use no option.*/
   $hODS = OGR_Dr_CreateDataSource( $hSFDriver, $strDestDataSource, NULL);
 
    if( $hODS == NULL )
        return OGRERR_FAILURE;
/* -------------------------------------------------------------------- */
/*      Process each data source layer.                                 */
/* -------------------------------------------------------------------- */
    for( $iLayer = 0; $iLayer < OGR_DS_GetLayerCount($hDS); $iLayer++ )
    {
        $hLayer = OGR_DS_GetLayer($hDS, $iLayer);

        if( $hLayer == NULL )
        {
            printf( "FAILURE: Couldn't fetch advertised layer %d!\n",
                    $iLayer );
            return OGRERR_FAILURE;

        }

        if( count($astrLayers) == 0 || 
            in_array(OGR_FD_GetName(OGR_L_GetLayerDefn($hLayer)), $astrLayers) != FALSE )
        {

        /*Assign spatial filter*/
            if($hSpatialFilter != NULL)
                OGR_L_SetSpatialFilter($hLayer, $hSpatialFilter);

        /*Assign attribute filter*/
            if($strWhere != NULL)
                $eErr = OGR_L_SetAttributeFilter($hLayer, $strWhere);
            if($eErr != OGRERR_NONE){
                printf("Erreur when setting attribute filter", $eErr);
                return $eErr;
            }

        /*Copy datasource file to a new data source file*/
            if( !TranslateLayer( $hDS, $hLayer, $hODS ) )
            return OGRERR_FAILURE;
        }
    }

/* -------------------------------------------------------------------- */
/*      Close down.                                                     */
/* -------------------------------------------------------------------- */
    OGR_DS_Destroy($hDS);
    OGR_DS_Destroy($hODS);
    
    return OGRERR_NONE;


}
/************************************************************************/
/*                               Usage()                                */
/************************************************************************/

    function Usage()

    {
            printf( "Usage: ogr2ogr [-f format_name] dst_datasource_name\n
             src_datasource_name [-spat Xmin Ymin Xmax Ymax]
             [-where where_string]\n");
    
            return OGRERR_NONE;
    }

/************************************************************************/
/*                           TranslateLayer()                           */
/************************************************************************/

function TranslateLayer( $hSrcDS, $hSrcLayer, $hDstDS )

{

/* -------------------------------------------------------------------- */
/*      Create the layer.                                               */
/* -------------------------------------------------------------------- */
    if( !OGR_DS_TestCapability( $hDstDS, ODsCCreateLayer ) )
    {
        printf( "%s data source does not support layer creation.\n",
                OGR_DS_GetName($hDstDS) );
        return FALSE;
    }

    $hFDefn = OGR_L_GetLayerDefn($hSrcLayer);


    $hSrcSpatialRef = OGR_L_GetSpatialRef($hSrcLayer);

    $hDstLayer = OGR_DS_CreateLayer( $hDstDS, OGR_FD_GetName($hFDefn),
                                     $hSrcSpatialRef, 
                                     OGR_FD_GetGeomType($hFDefn),
                                     NULL );
    if( $hDstLayer == NULL )
        return FALSE;

/* -------------------------------------------------------------------- */
/*      Add fields.                                                     */
/* -------------------------------------------------------------------- */
    for( $iField = 0; $iField < OGR_FD_GetFieldCount($hFDefn); $iField++ )
    {
        if( OGR_L_CreateField( $hDstLayer, OGR_FD_GetFieldDefn( $hFDefn, $iField),
                          0 /*bApproOK*/ ) != OGRERR_NONE )
            return FALSE;
    }
/* -------------------------------------------------------------------- */
/*      Transfer features.                                              */
/* -------------------------------------------------------------------- */
    OGR_L_ResetReading($hSrcLayer);

    while( ($hFeature = OGR_L_GetNextFeature($hSrcLayer)) != NULL )
    {

        $hDstFeature = OGR_F_Create( OGR_L_GetLayerDefn($hDstLayer) );

        if( OGR_F_SetFrom( $hDstFeature, $hFeature, FALSE /*bForgiving*/ ) != OGRERR_NONE )
        {
            OGR_F_Destroy($hFeature);
            
            printf("Unable to translate feature %d from layer %s.\n", 
                   OGR_F_GetFID($hFeature), OGR_FD_GetName($hFDefn) );
            return FALSE;
        }
        
        OGR_F_Destroy($hFeature);
        
        if( OGR_L_CreateFeature( $hDstLayer, $hDstFeature ) != OGRERR_NONE )
        {
            OGR_F_Destroy($hDstFeature);
            return FALSE;
        }

        OGR_F_Destroy($hDstFeature);
    }
    return TRUE;
}
?>

</PRE>
</BODY>
</HTML>











