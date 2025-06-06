import { Injectable } from '@angular/core';
import * as FileSaver from 'file-saver';
import * as XLSX from 'xlsx';
import { DatePipe } from '@angular/common'


const EXCEL_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8';
const EXCEL_EXTENSION = '.xlsx';

@Injectable({
  providedIn: 'root'
})
export class ExcelService {

  constructor(
    public datepipe: DatePipe
  ) { }

  public exportAsExcelFileNew(json: any[], excelFileName: string): void {

    const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(json);

    worksheet.s.fill = {
      bgColor: { rgb: 'b2b2b2' },
    };

    // console.log('worksheet',worksheet);
    const workbook: XLSX.WorkBook = { Sheets: { 'data': worksheet }, SheetNames: ['data'] };
    const excelBuffer: any = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' });
    //const excelBuffer: any = XLSX.write(workbook, { bookType: 'xlsx', type: 'buffer' });
    this.saveAsExcelFile(excelBuffer, excelFileName);
  }

  public exportAsExcelFileOLD(json: any[], excelFileName: string): void {

    const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(json);

    // const headerStyle = {
    //     fill: { fgColor: { rgb: "FFFF00" } },  // Yellow background
    //     font: { bold: true }
    // };

    // // Manually set styles for each header cell
    // worksheet['A1'] = {  s: headerStyle };
    // worksheet['B1'] = {  s: headerStyle };

    const workbook: XLSX.WorkBook = { Sheets: { 'data': worksheet }, SheetNames: ['data'] };
    const excelBuffer: any = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' });
    //const excelBuffer: any = XLSX.write(workbook, { bookType: 'xlsx', type: 'buffer' });
    this.saveAsExcelFile(excelBuffer, excelFileName);
  }

  public exportAsExcelFile(json: any[], headers: any[], excelFileName: string): void {
    // Create an empty worksheet
    const worksheet: XLSX.WorkSheet = {};

    // Add a single static name to the first row
    for (let i = 0; i < headers.length; i++) {
      XLSX.utils.sheet_add_aoa(worksheet, [headers[i]], { origin: 'A' + [i + 1] });
    }

    let startRow = 1;
    if (headers.length > 0) {
      startRow = headers.length + 2;
    }

    // Add dynamic headers to the third row
    const dynamicHeaders = Object.keys(json[0] || {});
    XLSX.utils.sheet_add_aoa(worksheet, [dynamicHeaders], { origin: 'A' + [startRow] });

    // Add data starting from the fourth row
    XLSX.utils.sheet_add_json(worksheet, json, { origin: 'A' + [startRow + 1], skipHeader: true });

    // Create the workbook
    const workbook: XLSX.WorkBook = {
      Sheets: { 'data': worksheet },
      SheetNames: ['data']
    };

    // Generate Excel buffer and save the file
    const excelBuffer: any = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' });
    this.saveAsExcelFile(excelBuffer, excelFileName);
  }

  private saveAsExcelFile(buffer: any, fileName: string): void {
    const data: Blob = new Blob([buffer], {
      type: EXCEL_TYPE
    });
    FileSaver.saveAs(data, fileName + '_export_' + this.datepipe.transform(new Date(), 'dd_MM_yyyy_hh_mm_ss') + EXCEL_EXTENSION);
  }

}
