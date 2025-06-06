import { Component, OnInit } from '@angular/core';
import { ToastrService } from 'ngx-toastr';
import { AdminService } from 'src/app/_services/admin.service';
import { PagerService } from 'src/app/_services/pager-service';
import * as moment from 'moment';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { ExcelService } from 'src/app/_services/excel.service';
import { filter } from 'rxjs/operators';

@Component({
  selector: 'app-inquiry-list',
  templateUrl: './inquiry-list.component.html',
  styleUrls: ['./inquiry-list.component.css']
})
export class InquiryListComponent implements OnInit {

  constructor(public adminService: AdminService, private toastr: ToastrService, private pagerService: PagerService, private router: Router, private route: ActivatedRoute, private excelService: ExcelService,
  ) { }


  isDashboard: boolean = false;
  todayDate: any;
  user_id: any;

  ngOnInit() {

    const currentUrl = this.router.url;
    this.isDashboard = currentUrl.startsWith('/dashboard');

    this.router.events
      .pipe(filter(event => event instanceof NavigationEnd))
      .subscribe((event: NavigationEnd) => {
        const url = event.urlAfterRedirects || event.url;
        this.isDashboard = url.startsWith('/dashboard');
      });


    if (this.isDashboard) {
      this.inquiryList();
      this.inquiryStatus();
    }
    else {
      // 
      this.route.queryParams.subscribe(params => {
        const hasParams = Object.keys(params).length > 0;
        // Default API call when there are no query parameters (first load)
        if (!hasParams) {
          this.inquiryList();
          this.inquiryType();
          this.inquiryStatus();
          this.inquiryStatusCounter();
        } else {
          this.inquiryObj.page = params['page'] ? Number(params['page']) : 1;
          this.inquiryObj.from_date = params['from'] ? params['from'] : '';
          this.inquiryObj.to_date = params['to'] ? params['to'] : '';
          this.inquiryObj.status_id = params['status_id'] ? params['status_id'] : '';
          // this.productObj.status = params.hasOwnProperty('status') && params['status'].length > 0 ? params['status'] : '';
          // this.productObj.search = params['search'] ? params['search'] : '';

          if (params['from'] && params['to']) {
            this.filters.date = {
              from_date: moment(new Date(params['from'])),
              to_date: moment(new Date(params['to']))
            };

            this.displayDates.start_time = moment(new Date(params['from'])).format('DD/MM/YYYY');
            this.displayDates.end_time = moment(new Date(params['to'])).format('DD/MM/YYYY');
          }

          this.inquiryList();
          this.inquiryType();
          this.inquiryStatus();
          this.inquiryStatusCounter();
        }
      });
    }
    // 

    // p

    this.permissionObj = this.adminService.userpermissions;

    for (const key in this.permissionObj) {
      if (this.permissionObj[key]['slug'] == 'inquiry') {
        for (const k in this.permissionObj[key]) {
          if (this.permissionObj[key][k] == 1) {
            this.permissions.push(k);
          }
        }
      }
    }


    // p



    // this.inquiryList();
    // this.inquiryType();
    // this.inquiryStatus();

    // const today = new Date();

    // this.todayDate = `${today.getDate().toString().padStart(2, '0')}-${(today.getMonth() + 1).toString().padStart(2, '0')}-${today.getFullYear()}`;
    const today = new Date();
    this.todayDate = `${today.getDate().toString().padStart(2, '0')}-${(today.getMonth() + 1).toString().padStart(2, '0')}-${today.getFullYear()}`;


    this.user_id = this.adminService.getTokenData().user_id;


  }


  permissions: any = [];
  permissionObj: any;

  //date S
  public displayDates = {
    start_time: "",
    end_time: ""
  }
  //For Date Filters //
  filters: any = {
    date: {
      from_date: "",
      to_date: "",
    }
  }



  removeDateRange() {

    this.router.navigate([], {
      queryParams: {
        'from': '',
        'to': '',
      },
      queryParamsHandling: 'merge', // This keeps existing query params
    });

    this.displayDates = {
      start_time: "",
      end_time: ""
    };

    this.filters.date = { from_date: "", to_date: "" };
    this.inquiryObj.from_date = "";
    this.inquiryObj.to_date = "";
    this.resetPagination();
    // this.getProductList();
    // this.inquiryList();

  }

  ranges: any = {
    'All': [31516200, moment().subtract('days').endOf('day')],
    'Today': [moment(), moment()],
    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
    'This Month': [moment().startOf('month'), moment().endOf('month')],
    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
  }
  invalidDates: moment.Moment[] = [moment().add(2, 'days'), moment().add(3, 'days'), moment().add(5, 'days')];
  isInvalidDate = (m: moment.Moment) => {
    return this.invalidDates.some(d => d.isSame(m, 'day'))
  }
  datesChanges(range) {
    if (range.startDate && range.endDate) {
      this.displayDates.start_time = "";
      this.displayDates.end_time = "";

      this.inquiryObj.from_date = "";
      this.inquiryObj.to_date = "";

      if (range.startDate && range.endDate) {
        this.displayDates.start_time = range.startDate.format('DD/MM/YYYY');
        this.displayDates.end_time = range.endDate.format('DD/MM/YYYY');

        this.inquiryObj.from_date = range.startDate.format('YYYY-MM-DD');
        this.inquiryObj.to_date = range.endDate.format('YYYY-MM-DD');
      }


      this.resetPagination();
      // this.inquiryList();
    }
  }

  setDateQuery(from_date, to_date) {
    if (from_date && to_date) {
      this.router.navigate([], {
        queryParams: {
          'from': from_date,
          'to': to_date,
        },
        queryParamsHandling: 'merge', // This keeps existing query params
      });
    }

    // console.log(this.productObj);
  }
  //For Date Filters //


  // date E

  // history for (today)
  formatUpdatedAt(dateStr: string): string {
    const date = new Date(dateStr);
    return `${date.getDate().toString().padStart(2, '0')}-${(date.getMonth() + 1).toString().padStart(2, '0')}-${date.getFullYear()}`;
  }

  inquiryObj: any = {
    page: 1,
    limit: 10,
    search: '',
    status_id: '',
    total: 0,
    sort_by: '',
    sort_order: '',
    from_date: '',
    to_date: '',
    order_by: '',
    order_type: '',
    // form_id: ''
  }

  inquiry_List: any = [];
  total_records: any = 0;

  isLoading: any = false;
  inquiryList() {
    this.isLoading = true;
    this.adminService.inquiryList(this.inquiryObj).subscribe((response: any) => {
      if (response.success == 1) {
        this.inquiry_List = response.list;
        this.total_records = response.total_records;
        this.inquiryObj.total = response.total_records;
        this.isLoading = false;
        if (this.inquiry_List && this.inquiry_List.length > 0) {
          this.setUsersPage(this.inquiryObj.page, 0);
        }
      }
      else {
        this.inquiry_List = [];
        this.isLoading = false;
        this.total_records = 0
      }
    })
  }




  //  page
  public usersPager: any = [];
  setUsersPage(page: number, flag: number) {
    this.usersPager = this.pagerService.getPager(this.inquiryObj.total, page, this.inquiryObj.limit);
    this.inquiryObj.page = this.usersPager.currentPage;

    this.router.navigate([], {
      queryParams: {
        page: this.inquiryObj.page
      },
      queryParamsHandling: 'merge', // This keeps existing query params
    });

    if (flag == 1) {
      // this.getProductList();
      this.inquiryList();
    }
  }


  resetPagination() {
    this.inquiryObj.page = 1;
  }


  // 


  onFilter() {
    this.inquiryObj.page = 1;
    this.inquiryList();
  }

  onSearchChange() {
    this.inquiryObj.page = 1;
    this.inquiryList();

    // this.searchSubject.next(this.categoryObj.search); // Trigger search with debounce
  }

  inquiry_type_list: any = [];

  inquiryType() {
    this.adminService.inquiryTypeList({}).subscribe((response: any) => {
      if (response.success == 1) {
        this.inquiry_type_list = response.list;
      }
      else {
        this.inquiry_type_list = [];
      }
    })
  }

  inquiry_status_list: any = [];

  inquiryStatus() {
    this.adminService.inquiryStatusList({}).subscribe((response: any) => {
      if (response.success == 1) {
        this.inquiry_status_list = response.list;
      }
      else {
        this.inquiry_status_list = [];
      }
    })
  }


  isOverlayOpen: boolean = false;
  inquiryDetailObj: any = {
    other: ''
  }
  toggleDetail(data: any) {
    this.inquiryDetailObj = data;
    // console.log(data);
    // console.log(this.inquiryDetailObj);

    this.isOverlayOpen = !this.isOverlayOpen;

  }

  closeDetail() {
    this.isOverlayOpen = false;
  }




  // follow ups
  public statusObj: any = {
    remarks: "",
    // login_user_id: this.adminService.getTokenData().user_id
  };


  //Inquiry Form Submit Funccion
  public isStatusChange: boolean = false;
  confirmChangeStatus(form) {


    console.log(this.statusObj);
    // return;

    // this.isStatusChange = true;
    if (form.valid) {

      this.statusObj.login_user_id = this.adminService.getTokenData().user_id;

      this.adminService
        .change_status_inquiry(this.statusObj)
        .subscribe((response: any) => {
          if (response.success == 1) {
            this.statusObj = {
              inquiry_id: "",
              remarks: "",
            };
            // this.isStatusChange = false;
            this.statusOpenF = false;
            this.inquiryList();
            this.inquiryStatusCounter();
            this.toastr.success(response.message);
          } else {
            // this.isStatusChange = false;
            // this.toastr.error(response.message);
          }
        });
    } else {
      // this.isStatusChange = false;

    }
  }



  statusOpenF: boolean = false;

  // change_Status(id: any, type: any) {
  //   if (this.statusObj.id == id) {
  //     this.statusObj.id = "";
  //   } else {
  //     this.statusObj.id = id;
  //     this.statusObj.type = type
  //   }
  // }
  cancelCahnge() {
    this.statusOpenF = false;
  }


  change_Status(data: any) {
    this.statusOpenF = true;
    this.statusObj.inquiry_id = data.id;
    this.statusObj.status_id = data.status_id;

    // console.log(this.statusObj);

  }


  // history

  dummyRecordsHistory = [1, 2, 3];

  historytoggleF: boolean = false;
  inquiryHistory_list: any = [];
  isLoadingHistory: boolean = false;

  inquiryHistoryList(id: any) {
    // this.historytoggleF = !this.historytoggleF;
    this.toggleOverlay();
    this.isLoadingHistory = true;

    this.adminService.inquiryHistoryListing({ inquiry_id: id }).subscribe((response: any) => {
      if (response.success == 1) {
        // console.log(response);
        this.inquiryHistory_list = response.list;
      }
      // else {
      // 	this.inquiryHistory_list = [];
      // }
      setTimeout(() => {
        this.isLoadingHistory = false;
      }, 250);

    }, (error) => {
      this.isLoadingHistory = false;
      console.error('task history api', error)
    })
  }

  toggleOverlay() {
    this.inquiryHistory_list = [];
    this.historytoggleF = !this.historytoggleF;

    if (this.historytoggleF) {
      document.body.classList.add('no-scroll-body');
    } else {
      document.body.classList.remove('no-scroll-body');
    }
  }


  inquiry_ststus_counter: any = {};

  inquiryStatusCounter() {
    this.adminService.inquiryStatusCounter({}).subscribe((response: any) => {
      if (response.success == 1) {
        this.inquiry_ststus_counter = response.data;
      }
      else {
        this.inquiry_ststus_counter = {}
      }
    })
  }

  onFilterstatusTab(id: any) {
    this.router.navigate([], {
      queryParams: {
        'status_id': id,
      },
      queryParamsHandling: 'merge', // This keeps existing query params
    });
    this.inquiryObj.status_id = id;
    this.inquiryObj.page = 1;
    this.inquiryList();
  }


  // excel

  exportList: any = [];
  exportAsXLSX(): void {
    var i = 1; let t = this;
    var obj = [];

    let headers = [];
    const payload = {
      limit: 1000000
    }
    this.adminService.inquiryList(payload).subscribe((response: any) => {
      if (response.success == 1) {
        this.exportList = response.list;

        this.exportList.map(function (a) {
          let exportData = {};

          exportData['Sr.No'] = i++;
          exportData['Name'] = a.name;
          exportData['Email'] = a.email ? a.email : "-";
          exportData['Contact_no'] = a.contact_no ? a.contact_no : "-";
          exportData['Message'] = a.message ? a.message : "";
          exportData['Type'] = a.form_name ? a.form_name : "-";
          exportData['Status'] = a.status ? a.status : "";
          exportData['Remarks'] = a.remarks ? a.remarks : "";
          exportData['Created At'] = a.created_at_formated ? a.created_at_formated : "";
          exportData['Last Updated At'] = a.last_updated_at ? a.last_updated_at : "";
          exportData['Closed At'] = a.closed_at ? a.closed_at : "";

          obj.push(exportData);
        });

        // this.excelService.exportAsExcelFile(obj, headers, "Categories");
        this.excelService.exportAsExcelFile(obj, headers, "Inquiry")
      }
      else {
      }
    });
  }

  // 

  objectKeys(obj: any): string[] {
    return obj ? Object.keys(obj) : [];
  }
}
